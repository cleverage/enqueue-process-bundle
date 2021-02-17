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
use CleverAge\ProcessBundle\Model\ProcessState;
use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Push a command to the message broker
 */
class PushEventTask extends AbstractConfigurableTask
{
    /** @var ProducerInterface */
    protected $producer;

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
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $properties = [];
        if ($options['inherit_context']) {
            $properties['context'] = $state->getContext();
        } else {
            $properties['context'] = $options['context'];
        }
        $message = new Message($state->getInput(), $properties);
        $this->producer->sendEvent($options['topic'], $message);
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'topic',
            ]
        );
        $resolver->setDefaults(
            [
                'inherit_context' => true,
                'context' => [],
            ]
        );
        $resolver->setAllowedTypes('inherit_context', ['bool']);
        $resolver->setAllowedTypes('context', ['null', 'array']);
    }
}
