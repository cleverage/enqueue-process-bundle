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

use CleverAge\ProcessBundle\Manager\ProcessManager;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

/**
 * Read events from the queue and dispatch them to processes
 */
class ProcessTopicConsumer implements PsrProcessor
{
    /** @var ProcessManager */
    protected $processManager;

    /** @var LoggerInterface */
    protected $logger;

    /** @var array */
    protected $topicConfigurations;

    /**
     * @param ProcessManager  $processManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProcessManager $processManager,
        LoggerInterface $logger
    ) {
        $this->processManager = $processManager;
        $this->logger = $logger;
    }

    /**
     * @param array $topicConfigurations
     */
    public function setTopicConfigurations(array $topicConfigurations): void
    {
        $this->topicConfigurations = $topicConfigurations;
    }

    /**
     * {@inheritDoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        $topic = $message->getProperty('enqueue.topic_name');
        $processCode = $this->topicConfigurations[$topic]['process'];
        $input = json_decode($message->getBody(), true);
        if (null === $input) {
            $input = $message->getBody();
        }

        $this->logger->info(
            "Launching process {$processCode}",
            [
                'input' => $message->getBody(),
            ]
        );
        try {
            $this->processManager->execute($processCode, $input);
        } catch (\Exception $e) {
            if ($this->topicConfigurations[$topic]['throw_exception'] ?? false) {
                throw $e;
            }
            $this->logger->critical(
                "Process {$processCode} stopped with error: {$e->getMessage()}",
                [
                    'input' => $message->getBody(),
                    'exception' => $e,
                ]
            );

            return self::REJECT;
        }

        return self::ACK;
    }
}
