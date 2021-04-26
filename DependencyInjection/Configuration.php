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
 * Configuration for tbbc_rest_util
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
        $treeBuilder = new TreeBuilder('tbbc_rest_util');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC for symfony/config < 4.2
            $rootNode = $treeBuilder->root('tbbc_rest_util');
        }

        $this->addErrorSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Parses the tbbc_rest_util.error config section
     * Example for yaml driver:
     * tbbc_rest_util:
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
                        ->scalarNode('error_resolver')
                            ->defaultValue('tbbc_rest_util.error.error_resolver')
                        ->end()
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
                                    ->scalarNode('error_message')->defaultNull()->end()
                                    ->scalarNode('error_extended_message')->defaultNull()->end()
                                    ->scalarNode('error_more_info_url')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
