<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/EnqueueProcessBundle package.
 *
 * Copyright (c) 2015-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\EnqueueProcessBundle\DependencyInjection;

use CleverAge\EnqueueProcessBundle\Subscriber\ProcessCommandConsumer;
use CleverAge\EnqueueProcessBundle\Subscriber\ProcessTopicConsumer;
use Enqueue\Client\Config;
use Sidus\BaseBundle\DependencyInjection\SidusBaseExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class CleverAgeEnqueueProcessExtension extends SidusBaseExtension
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        parent::load($configs, $container);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $processTopicConsumerDefinition = $container->getDefinition(ProcessTopicConsumer::class);
        $processTopicConsumerDefinition->addMethodCall('setTopicConfigurations', [$config['topics']]);
        foreach ($config['topics'] as $topicName => $topicConfig) {
            $processTopicConsumerDefinition->addTag(
                'enqueue.client.processor',
                [
                    'topicName' => $topicName,
                    'queueName' => $topicConfig['queue_name'],
                    'queueNameHardcoded' => $topicConfig['queue_name_hardcoded'],
                ]
            );
        }

        $processCommandConsumerDefinition = $container->getDefinition(ProcessCommandConsumer::class);
        $processCommandConsumerDefinition->addMethodCall('setCommandConfigurations', [$config['commands']]);
        foreach ($config['commands'] as $commandName => $commandConfig) {
            $processCommandConsumerDefinition->addTag(
                'enqueue.client.processor',
                [
                    'topicName' => Config::COMMAND_TOPIC,
                    'queueName' => $commandConfig['queue_name'],
                    'queueNameHardcoded' => $commandConfig['queue_name_hardcoded'],
                    'processorName' => $commandName,
                    'exclusive' => $commandConfig['exclusive'],
                ]
            );
        }
    }
}
