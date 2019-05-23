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

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link
 * http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Configuration implements ConfigurationInterface
{
    protected $root;

    /**
     * @param string $root
     */
    public function __construct($root = 'clever_age_process_enqueue')
    {
        $this->root = $root;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->root);

        $topicDefinition = $rootNode
            ->children()
                ->arrayNode('topics')
                    ->defaultValue([])
                    ->prototype('array')
                        ->children();

        $this->appendTopicDefinition($topicDefinition);

        $commandDefinition = $topicDefinition->end()
                    ->end()
                ->end()
                ->arrayNode('commands')
                    ->defaultValue([])
                    ->prototype('array')
                        ->children();

        $this->appendCommandDefinition($commandDefinition);

        $commandDefinition->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @param NodeBuilder $classDefinition
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function appendTopicDefinition(NodeBuilder $classDefinition): void
    {
        $classDefinition
            ->scalarNode('process')->isRequired()->end()
            ->scalarNode('queueName')->defaultNull()->end()
            ->scalarNode('queueNameHardcoded')->defaultFalse()->end()
            ->booleanNode('throw_exception')->defaultFalse()->end();
    }

    /**
     * @param NodeBuilder $classDefinition
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function appendCommandDefinition(NodeBuilder $classDefinition): void
    {
        $classDefinition
            ->scalarNode('process')->isRequired()->end()
            ->scalarNode('queueName')->defaultNull()->end()
            ->scalarNode('queueNameHardcoded')->defaultFalse()->end()
            ->scalarNode('exclusive')->defaultFalse()->end()
            ->booleanNode('throw_exception')->defaultTrue()->end();
    }
}
