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

use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;

/**
 * Read events from the queue and dispatch them to processes
 */
class ProcessTopicConsumer extends AbstractProcessConsumer
{
    /** @var array */
    protected $topicConfigurations;

    /**
     * @param array $topicConfigurations
     */
    public function setTopicConfigurations(array $topicConfigurations): void
    {
        $this->topicConfigurations = $topicConfigurations;
    }

    /**
     * @param PsrMessage $message
     * @param string     $option
     *
     * @return mixed
     */
    protected function getConfigOption(PsrMessage $message, string $option)
    {
        return $this->topicConfigurations[$message->getProperty('enqueue.topic_name')][$option];
    }

    /**
     * {@inheritDoc}
     */
    protected function handleOutput(PsrMessage $message, PsrContext $context, $output): string
    {
        return self::ACK;
    }
}
