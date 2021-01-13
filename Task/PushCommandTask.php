<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/EnqueueProcessBundle package.
 *
 * Copyright (c) 2015-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\EnqueueProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Rpc\Promise;
use Interop\Queue\PsrMessage;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Push a command to the message broker
 */
class PushCommandTask extends AbstractConfigurableTask implements FlushableTaskInterface
{
    /** @var ProducerInterface */
    protected $producer;

    // Runtime properties

    /** @var Promise[] */
    protected $replies = [];

    /**
     * @param ProducerInterface $producer
     */
    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param ProcessState $state
     */
    public function flush(ProcessState $state): void
    {
        $results = [];
        foreach ($this->replies as $key => $reply) {
            $response = $reply->receive($this->getOption($state, 'timeout'));
            if ($response) {
                $results[] = $response->getBody();
            }
        }
        $this->replies = [];
        if (0 === \count($results)) {
            $state->setSkipped(true);
        }
        $state->setOutput($results);
    }

    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state): void
    {
        $options = $this->getOptions($state);
        $properties = [];
        if ($options['inherit_context']) {
            $properties['context'] = $state->getContext();
        } else {
            $properties['context'] = $options['context'];
        }
        $message = new Message($state->getInput(), $properties);
        $reply = $this->producer->sendCommand($options['command'], $message, true);
        if (!$reply instanceof Promise) {
            throw new \UnexpectedValueException('No promise found for command');
        }
        $this->replies[] = $reply;

        $results = [];
        $endTime = ($options['timeout'] / 1000) + time();
        while (\count($this->replies) >= $options['max_concurrent_replies']) {
            if (time() > $endTime) {
                $count = \count($this->replies);
                throw new \RuntimeException(
                    "Timeout exceeded for task {$state->getTaskConfiguration()->getCode()}, {$count} remaining messages"
                );
            }
            // Wait and fetch more results
            $this->unstackQueue($results);
        }

        if (0 === \count($results)) {
            $state->setSkipped(true);
        }
        $state->setOutput($results);
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'command',
            ]
        );
        $resolver->setDefaults(
            [
                'max_concurrent_replies' => 1,
                'timeout' => 60000,
                'inherit_context' => true,
                'context' => [],
            ]
        );
        $resolver->setAllowedTypes('max_concurrent_replies', ['int']);
        $resolver->setAllowedTypes('inherit_context', ['bool']);
        $resolver->setAllowedTypes('context', ['null', 'array']);
    }

    /**
     * @param array $results
     */
    protected function unstackQueue(array &$results = []): void
    {
        if (0 === \count($this->replies)) {
            throw new \UnexpectedValueException('Empty queue');
        }

        foreach ($this->replies as $key => $reply) {
            $response = $reply->receiveNoWait();
            if ($response instanceof PsrMessage) {
                unset($this->replies[$key]);
                $results[] = $response->getBody();
            }
        }
    }
}
