<?php

/**
 * This file is part of TbbcRestUtilBundle
 *
 * (c) The Big Brains Company <contact@thebigbrainscompany.org>
 *
 */

namespace Tbbc\RestUtilBundle\Tests\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Tbbc\RestUtilBundle\DependencyInjection\Configuration;

/**
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionErrorMappingProcessing()
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, array(
            array(
                'error' => array(
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
                            'factory' => 'custom',
                            'http_status_code' => 400,
                            'error_code' => 110,
                            'error_message' => 'Validation failed',
                        ),
                    )
                ),
            )
        ));

        $expected = array(
            'InvalidArgumentException' => array(
                'class' => '\RuntimeException',
                'factory' => 'default',
                'http_status_code' => 500,
                'error_code' => 123,
                'error_message' => 'Server error',
            ),
            'FormException' => array(
                'class' => 'My\FormException',
                'factory' => 'custom',
                'http_status_code' => 400,
                'error_code' => 110,
                'error_message' => 'Validation failed',
            ),
        );

        $this->assertEquals($expected, $config['error']['exception_mapping']);
    }
}
