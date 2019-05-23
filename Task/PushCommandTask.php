<?php
/*
 * This file is part of the CleverAge/EnqueueProcessBundle package.
 *
 * Copyright (c) 2015-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\EnqueueProcessBundle\Task;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\FlushableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
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
            $results[] = $response->getBody();
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
        $reply = $this->producer->sendCommand($this->getOption($state, 'command'), $state->getInput(), true);
        if (!$reply instanceof Promise) {
            throw new \UnexpectedValueException('No promise found for command');
        }
        $this->replies[] = $reply;

        $options = $this->getOptions($state);
        $results = [];
        while (\count($this->replies) >= $options['max_concurrent_replies']) {
            // Wait and fetch more results
            $results[] = $this->unstackQueue($state)->getBody();
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
            ]
        );
        $resolver->setAllowedTypes('max_concurrent_replies', ['int']);
    }

    /**
     * @param ProcessState $state
     *
     * @return PsrMessage
     */
    protected function unstackQueue(ProcessState $state): PsrMessage
    {
        if (0 === \count($this->replies)) {
            throw new \UnexpectedValueException('Empty queue');
        }

        $endTime = ($this->getOption($state, 'timeout') / 1000) + time();
        while (time() <= $endTime) {
            foreach ($this->replies as $key => $reply) {
                $response = $reply->receiveNoWait();
                if ($response instanceof PsrMessage) {
                    unset($this->replies[$key]);

                    return $response;
                }
            }
            sleep(1);
        }

        $count = \count($this->replies);
        throw new \RuntimeException(
            "Timeout exceeded for task {$state->getTaskConfiguration()->getCode()}, {$count} remaining messages"
        );
    }
}
