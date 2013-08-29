<?php

/**
 * This file is part of TbbcRestUtilBundle
 *
 * (c) The Big Brains Company <contact@thebigbrainscompany.org>
 *
 */

namespace Tbbc\RestUtilBundle\Tests\DependencyInjection;

use Tbbc\RestUtil\Error\Error;
use Tbbc\RestUtil\Error\ErrorFactoryInterface;
use Tbbc\RestUtil\Error\ErrorResolver;
use Tbbc\RestUtil\Error\Mapping\ExceptionMap;
use Tbbc\RestUtil\Error\Mapping\ExceptionMapping;
use Tbbc\RestUtilBundle\TbbcRestUtilBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tbbc\RestUtilBundle\DependencyInjection\TbbcRestUtilExtension;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 */
class TbbcRestUtilExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var TbbcRestUtilBundle
     */
    private $extension;

    public function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new TbbcRestUtilExtension();

        $this->container->registerExtension($this->extension);

        $bundle = new TbbcRestUtilBundle();
        $bundle->build($this->container);
    }

    public function tearDown()
    {
        unset($this->container, $this->extension);
    }

    public function testErrorExceptionMapIsConstructedCorrectly()
    {
        $config = $this->getConfig();

        $this->extension->load($config, $this->container);
        $this->container->compile();

        $expectedExceptionMap = $this->getExceptionMap();

        $this->assertEquals($expectedExceptionMap, $this->container->get('tbbc_restutil.error.mapping.exception_map'));
    }

    public function testErrorResolverIsConstructedCorrectly()
    {
        $config = $this->getConfig();
        $this->extension->load($config, $this->container);
        $this->container->compile();

        // Manual construction of expected ErrorResolver
        $exceptionMap = $this->getExceptionMap();
        $expectedErrorResolver = new ErrorResolver($exceptionMap);

        $this->assertEquals($expectedErrorResolver, $this->container->get('tbbc_restutil.error.error_resolver'));
    }

    /**
     * Returns ExceptionMap corresponding to the getConfig() result
     *
     * @return ExceptionMap
     */
    private function getExceptionMap()
    {
        $exceptionMap = new ExceptionMap();
        $exceptionMap
            ->add(new ExceptionMapping(array(
                'exceptionClassName' => '\RuntimeException',
                'factory' => '__DEFAULT__',
                'httpStatusCode' => 500,
                'errorCode' => 500123,
                'errorMessage' => 'Server error',
                'errorExtendedMessage' => 'Extended message',
                'errorMoreInfoUrl' => 'http://api.my.tld/doc/error/500123',
            )))
        ;

        $exceptionMap->add(new ExceptionMapping(array(
                'exceptionClassName' => 'My\FormException',
                'factory' => 'form',
                'httpStatusCode' => 400,
                'errorCode' => 400110,
                'errorMessage' => 'Validation failed',
                'errorExtendedMessage' => 'Extended message',
                'errorMoreInfoUrl' => 'http://api.my.tld/doc/error/400110',
            )))
        ;

        return $exceptionMap;
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        return array(
            "tbbc_restutil" => array (
                "error" => array (
                    'exception_mapping' => array(
                        'InvalidArgumentException' => array(
                            'class' => '\RuntimeException',
                            'factory' => 'default',
                            'http_status_code' => 500,
                            'error_code' => 500123,
                            'error_message' => 'Server error',
                            'error_extended_message' => 'Extended message',
                            'error_more_info_url' => 'http://api.my.tld/doc/error/500123',

                        ),
                        'FormException' => array(
                            'class' => 'My\FormException',
                            'factory' => 'form',
                            'http_status_code' => 400,
                            'error_code' => 400110,
                            'error_message' => 'Validation failed',
                            'error_extended_message' => 'Extended message',
                            'error_more_info_url' => 'http://api.my.tld/doc/error/400110',
                        ),
                    ),
                ),
            ),
        );
    }
}
