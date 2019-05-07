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

        $classDefinition = $rootNode
            ->children()
                ->arrayNode('topics')
                    ->defaultValue([])
                    ->prototype('array')
                        ->children();

        $this->appendTopicsDefinition($classDefinition);

        $classDefinition->end()
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
    protected function appendTopicsDefinition(NodeBuilder $classDefinition): void
    {
        $classDefinition
            ->scalarNode('process')->isRequired()->end()
            ->booleanNode('throw_exception')->defaultFalse()->end();
    }
}
