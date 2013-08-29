<?php

/**
 * This file is part of tbbc/rest-util
 *
 * (c) The Big Brains Company <contact@thebigbrainscompany.org>
 *
 */

namespace Tbbc\RestUtilBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Extension for tbbc_rest_util
 *
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 */
class TbbcRestUtilExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('error.xml');

        // factories loading
        if (true === (bool) $config['error']['use_bundled_factories']) {
            $loader->load('error_factories.xml');
        }

        // mapping configuration
        $this->configureErrorExceptionMapping($config, $container);
    }

    protected function configureErrorExceptionMapping(array $config, ContainerBuilder $container)
    {
        if (!isset($config['error']['exception_mapping']) || empty($config['error']['exception_mapping'])) {
            return;
        }

        $exceptionMapDefinition = $container->getDefinition('tbbc_rest_util.error.mapping.exception_map');
        $exceptionMappingClass = $container->getParameter('tbbc_rest_util.error.mapping.exception_mapping.class');
        foreach($config['error']['exception_mapping'] as $mappingConfig) {
            $mappingDefinition = new Definition($exceptionMappingClass, array(array(
                'exceptionClassName' => $mappingConfig['class'],
                'factory' => 'default' == $mappingConfig['factory'] ? '__DEFAULT__' : $mappingConfig['factory'],
                'httpStatusCode' => $mappingConfig['http_status_code'],
                'errorCode' => $mappingConfig['error_code'],
                'errorMessage' => $mappingConfig['error_message'],
                'errorExtendedMessage' => $mappingConfig['error_extended_message'],
                'errorMoreInfoUrl' => $mappingConfig['error_more_info_url'],
            )));

            $exceptionMapDefinition->addMethodCall('add', array($mappingDefinition));
        }
    }
}
