<?php
/*
 * This file is part of the CleverAge/EnqueueProcessBundle package.
 *
 * Copyright (c) 2015-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\EnqueueProcessBundle\DependencyInjection;

use CleverAge\EnqueueProcessBundle\Subscriber\ProcessConsumer;
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
    public function load(array $configs, ContainerBuilder $container)
    {
        parent::load($configs, $container);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $processDefinition = $container->getDefinition(ProcessConsumer::class);
        $processDefinition->addMethodCall('setTopicsMapping', [$config['topics']]);
        ProcessConsumer::setTopicsMapping($config['topics']);
    }
}
