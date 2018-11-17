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
use Enqueue\Client\TopicSubscriberInterface;
use Psr\Log\LoggerInterface;

/**
 * Read events from the queue and dispatch them to processes
 */
class ProcessConsumer implements PsrProcessor, TopicSubscriberInterface
{
    /** @var array */
    protected static $topicsMapping;

    /** @var ProcessManager */
    protected $processManager;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ProcessManager  $processManager
     * @param LoggerInterface $logger
     */
    public function __construct(ProcessManager $processManager, LoggerInterface $logger)
    {
        $this->processManager = $processManager;
        $this->logger = $logger;
    }

    /**
     * @param array $topicsMapping
     */
    public static function setTopicsMapping(array $topicsMapping): void
    {
        static::$topicsMapping = $topicsMapping;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return array_keys(static::$topicsMapping);
    }

    /**
     * The method has to return either self::ACK, self::REJECT, self::REQUEUE string.
     *
     * The method also can return an object.
     * It must implement __toString method and the method must return one of the constants from above.
     *
     * @param PsrMessage $message
     * @param PsrContext $context
     *
     * @throws \Exception
     *
     * @return string|object with __toString method implemented
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        $topic = $message->getProperty('enqueue.topic_name');
        $processCode = static::$topicsMapping[$topic]['process'];
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
        $this->processManager->execute($processCode, $input);

        return self::ACK;
    }
}
