<?php declare(strict_types=1);
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
use CleverAge\ProcessBundle\Model\ProcessState;
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
        $this->producer->sendEvent($this->getOption($state, 'topic'), $state->getInput());
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
    }
}
