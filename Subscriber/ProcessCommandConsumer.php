<?php
/*
 * This file is part of the CleverAge/EnqueueProcessBundle package.
 *
 * Copyright (c) 2015-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\EnqueueProcessBundle\Subscriber;

use Enqueue\Consumption\Result;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;

/**
 * Read command input from the queue and dispatch them to a single process
 */
class ProcessCommandConsumer extends AbstractProcessConsumer
{
    /** @var array */
    protected $commandConfigurations;

    /**
     * @param array $commandConfigurations
     */
    public function setCommandConfigurations(array $commandConfigurations): void
    {
        $this->commandConfigurations = $commandConfigurations;
    }

    /**
     * @param PsrMessage $message
     * @param string     $option
     *
     * @return mixed
     */
    protected function getConfigOption(PsrMessage $message, string $option)
    {
        return $this->commandConfigurations[$message->getProperty('enqueue.command_name')][$option];
    }

    /**
     * {@inheritDoc}
     */
    protected function handleOutput(PsrMessage $message, PsrContext $context, $output)
    {
        return Result::reply($context->createMessage($output));
    }
}
