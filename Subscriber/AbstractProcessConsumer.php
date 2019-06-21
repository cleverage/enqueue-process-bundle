<?php

namespace CleverAge\EnqueueProcessBundle\Subscriber;

use CleverAge\ProcessBundle\Manager\ProcessManager;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

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

        return $this->handleOutput($message, $context, $output);
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
