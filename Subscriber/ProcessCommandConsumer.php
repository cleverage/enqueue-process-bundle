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
use Enqueue\Consumption\Result;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

/**
 * Read command input from the queue and dispatch them to a single process
 */
class ProcessCommandConsumer implements PsrProcessor
{
    /** @var ProcessManager */
    protected $processManager;

    /** @var LoggerInterface */
    protected $logger;

    /** @var array */
    protected $commandConfigurations;

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
     * @param array $commandConfigurations
     */
    public function setCommandConfigurations(array $commandConfigurations): void
    {
        $this->commandConfigurations = $commandConfigurations;
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
        $command = $message->getProperty('enqueue.command_name');
        $processCode = $this->commandConfigurations[$command]['process'];
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
            $output = $this->processManager->execute($processCode, $input);
        } catch (\Exception $e) {
            if ($this->commandConfiguration['throw_exception'] ?? false) {
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

        return Result::reply($context->createMessage($output));
    }
}
