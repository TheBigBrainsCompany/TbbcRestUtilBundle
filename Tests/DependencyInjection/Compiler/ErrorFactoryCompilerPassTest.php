<?php

/**
 * This file is part of TbbcRestUtilBundle
 *
 * (c) The Big Brains Company <contact@thebigbrainscompany.org>
 *
 */

namespace Tbbc\RestUtilBundle\Tests\DependencyInjection\Compiler;

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
class ErrorFactoryCompilerPassTest extends \PHPUnit_Framework_TestCase
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

    public function testErrorResolverWithCustomErrorFactoriesIsConstructedCorrectly()
    {
        $config = $this->getConfig();
        $this->extension->load($config, $this->container);

        // add custom factory definition to the container
        $formErrorFactoryDefinition = new Definition(
            '\Tbbc\RestUtilBundle\Tests\DependencyInjection\Compiler\FormErrorFactory');
        $formErrorFactoryDefinition->addTag('tbbc_restutil.error_factory');
        $this->container->addDefinitions(array($formErrorFactoryDefinition));

        $this->container->compile();

        // Manual construction of expected ErrorResolver
        $exceptionMap = $this->getExceptionMap();
        $expectedErrorResolver = new ErrorResolver($exceptionMap);
        $formErrorFactory = new FormErrorFactory();
        $expectedErrorResolver->registerFactory($formErrorFactory);

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
                'factory'            => '__DEFAULT__',
                'httpStatusCode'     => 500,
                'errorCode'          => 123,
                'errorMessage'       => 'Server error',
            )))
        ;

        $exceptionMap->add(new ExceptionMapping(array(
                'exceptionClassName' => 'My\FormException',
                'factory'            => 'form',
                'httpStatusCode'     => 400,
                'errorCode'          => 110,
                'errorMessage'       => 'Validation failed',
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
                            'error_code' => 123,
                            'error_message' => 'Server error',
                        ),
                        'FormException' => array(
                            'class' => 'My\FormException',
                            'factory' => 'form',
                            'http_status_code' => 400,
                            'error_code' => 110,
                            'error_message' => 'Validation failed',
                        ),
                    ),
                ),
            ),
        );
    }
}

class FormErrorFactory implements ErrorFactoryInterface
{
    public function getIdentifier()
    {
        return 'form';
    }

    public function createError(\Exception $exception, ExceptionMapping $mapping)
    {
        return new Error($mapping->getHttpStatusCode(), $mapping->getErrorCode(), $mapping->getErrorMessage());
    }
}
