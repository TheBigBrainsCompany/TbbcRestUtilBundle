<?php

/**
 * This file is part of TbbcRestUtilBundle
 *
 * (c) The Big Brains Company <contact@thebigbrainscompany.org>
 *
 */

namespace Tbbc\RestUtilBundle\Tests\Error\Factory;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Tbbc\RestUtil\Error\Error;
use Tbbc\RestUtil\Error\ErrorResolver;
use Tbbc\RestUtil\Error\Mapping\ExceptionMap;
use Tbbc\RestUtil\Error\Mapping\ExceptionMapping;
use Tbbc\RestUtil\Error\Mapping\ExceptionMappingInterface;
use Tbbc\RestUtilBundle\Error\Exception\FormErrorException;
use Tbbc\RestUtilBundle\Error\Factory\FormErrorFactory;

/**
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 */
class FormErrorFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ErrorResolver
     */
    private $errorResolver;

    /**
     * @var ExceptionMappingInterface
     */
    private $formErrorExceptionMapping;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FormInterface
     */
    private $form;

    public function setUp()
    {
        $this->formErrorExceptionMapping = new ExceptionMapping(array(
            'exceptionClassName' => '\Tbbc\RestUtilBundle\Error\Exception\FormErrorException',
            'factory' => 'form_error',
            'httpStatusCode' => 400,
            'errorCode' => 400101,
            'errorMessage' => 'An error has occurred while processing your request, make sure your data are valid',
            'errorExtendedMessage' => 'Extended message',
            'errorMoreInfoUrl' => 'http://api.my.tld/doc/error/400101',
        ));

        $exceptionMap = new ExceptionMap();
        $exceptionMap->add($this->formErrorExceptionMapping);
        $this->errorResolver = new ErrorResolver($exceptionMap);

        $this->form = $this->createForm();

        $this->translator = $this->getMock('\Symfony\Component\Translation\TranslatorInterface');
    }

    public function tearDown()
    {
        unset($this->errorResolver, $this->translator, $this->form, $this->formErrorExceptionMapping);
    }


    public function testFormErrorFactoryCreateErrorReturnNullIfExceptionNotSupported()
    {
        $formErrorFactory = new FormErrorFactory();
        $exception = new \InvalidArgumentException();

        $this->assertNull($formErrorFactory->createError($exception, $this->formErrorExceptionMapping));
    }

    public function testFormErrorFactoryCreateErrorReturnsValidErrorObject()
    {
        $formErrorFactory = new FormErrorFactory();
        $formErrorException = new FormErrorException($this->form, $this->translator);

        $expectedError = new Error(
            400,
            400101,
            'An error has occurred while processing your request, make sure your data are valid',
            array(
                'form_errors' => null,
                'field_errors' => array(),
            ),
            'http://api.my.tld/doc/error/400101'
        );

        $actualError = $formErrorFactory->createError($formErrorException, $this->formErrorExceptionMapping);

        $this->assertEquals($expectedError, $actualError);
    }

    /**
     * @return FormInterface
     */
    protected function createForm()
    {
        $form = $this->getBuilder('tbbc_rest_util_bundle_test', new EventDispatcher())->getForm();
        $form->bind('foobar');
        $form->addError(new FormError('Error!'));

        return $form;
    }

    /**
     * @param string                   $name
     * @param EventDispatcherInterface $dispatcher
     * @param string                   $dataClass
     *
     * @return FormBuilder
     */
    protected function getBuilder($name = 'name', EventDispatcherInterface $dispatcher = null, $dataClass = null)
    {
        $factory = $this->getMock('\Symfony\Component\Form\FormFactoryInterface');

        return new FormBuilder($name, $dataClass, $dispatcher, $factory);
    }
}
