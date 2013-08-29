<?php

/**
 * This file is part of tbbc/rest-util
 *
 * (c) The Big Brains Company <contact@thebigbrainscompany.org>
 *
 */

namespace Tbbc\RestUtilBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for tbbc_restutil
 *
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tbbc_restutil');

        $this->addErrorSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Parses the tbbc_restutil.error config section
     * Example for yaml driver:
     * tbbc_restutil:
     *     error:
     *         use_bundled_factories: true
     *         exception_mapping:
     *             InvalidArgumentException:
     *                 class: "\InvalidArgumentException"
     *                 factory: default
     *                 http_status_code: 400
     *                 error_code: 400101
     *                 error_message: "Invalid argument exception"
     *                 error_extended_message: "More extended message"
     *                 error_more_info_url: "http://api.my.tld/doc/error/400101"
     *
     * @param ArrayNodeDefinition $node
     * @return void
     */
    private function addErrorSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('error')
                    ->children()
                        ->booleanNode('use_bundled_factories')->defaultTrue()->end()
                        ->arrayNode('exception_mapping')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('class')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('factory')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->defaultValue('default')
                                    ->end()
                                    ->scalarNode('http_status_code')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('error_code')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('error_message')->cannotBeEmpty()->end()
                                    ->scalarNode('error_extended_message')->end()
                                    ->scalarNode('error_more_info_url')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
