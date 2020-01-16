<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/EnqueueProcessBundle package.
 *
 * Copyright (c) 2015-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\EnqueueProcessBundle\Subscriber;

use CleverAge\ProcessBundle\Manager\ProcessManager;
use Enqueue\Consumption\Result;
use Interop\Amqp\AmqpMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;
use function get_class;

/**
 * Common methods for both command and topic consumers
 */
abstract class AbstractProcessConsumer implements PsrProcessor
{
    /** @var ProcessManager */
    protected $processManager;

    /** @var LoggerInterface */
    protected $logger;

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
     * {@inheritDoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        $processCode = $this->getConfigOption($message, 'process');
        $input = $message->getBody();
        if ($this->getConfigOption($message, 'json_decode')) {
            $input = json_decode($message->getBody(), true);
        }

        $this->logger->info(
            "Launching process {$processCode}",
            [
                'input' => $message->getBody(),
            ]
        );
        try {
            $output = $this->processManager->execute($processCode, $input);
        } catch (\Exception $e) {
            return $this->handleException($message, $processCode, $e);
        }

        return $this->handleOutput($message, $context, $output);
    }

    /**
     * @param PsrMessage $message
     * @param string     $processCode
     * @param \Exception $e
     *
     * @return string
     */
    protected function handleException(PsrMessage $message, string $processCode, \Exception $e): string
    {
        $this->logger->critical(
            "Process {$processCode} stopped with error: {$e->getMessage()}",
            [
                'input' => $message->getBody(),
                'exception' => $e,
            ]
        );

        $maxRequeueCount = $this->getConfigOption($message, 'max_requeue');
        if (null !== $maxRequeueCount) {
            if ($message instanceof AmqpMessage) {
                if ($message->getDeliveryTag() <= $maxRequeueCount) {
                    $this->logger->warning(
                        "Requeuing message, for the {$message->getDeliveryTag()} time",
                        [
                            'input' => $message->getBody(),
                            'exception' => $e,
                        ]
                    );

                    return self::REQUEUE;
                }
            } else {
                $this->logger->critical(
                    'Message is not an AmqpMessage so max_requeue option is not supported',
                    [
                        'message_class' => get_class($message),
                    ]
                );
            }
        } elseif ($this->getConfigOption($message, 'throw_exception')) {
            throw $e; // Throwing exception is not compatible with max count requeuing messages
        }

        $errorStrategy = $this->getConfigOption($message, 'error_strategy');
        if ('reject' === $errorStrategy) {
            return self::REJECT;
        }
        if ('requeue' === $errorStrategy) {
            return self::REQUEUE;
        }
        if ('ack' === $errorStrategy) {
            return self::ACK;
        }

        throw new \UnexpectedValueException("Unexpected error strategy {$errorStrategy}", 0, $e);
    }

    /**
     * @param PsrMessage $message
     * @param string     $option
     *
     * @return mixed
     */
    abstract protected function getConfigOption(PsrMessage $message, string $option);

    /**
     * @param PsrMessage $message
     * @param PsrContext $context
     * @param mixed      $output
     *
     * @return string|Result
     */
    abstract protected function handleOutput(PsrMessage $message, PsrContext $context, $output);
}
