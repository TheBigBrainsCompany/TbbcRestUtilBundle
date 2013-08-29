<?php

/**
 * This file is part of TbbcRestUtilBundle
 *
 * (c) The Big Brains Company <contact@thebigbrainscompany.org>
 *
 */

namespace Tbbc\RestUtilBundle\Error\Factory;

use Tbbc\RestUtil\Error\Error;
use Tbbc\RestUtil\Error\ErrorFactoryInterface;
use Tbbc\RestUtil\Error\Mapping\ExceptionMappingInterface;
use Tbbc\RestUtilBundle\Error\Exception\FormErrorException;

/**
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 */
class FormErrorFactory implements ErrorFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        return 'tbbc_restutil_form_error';
    }

    /**
     * {@inheritDoc}
     */
    public function createError(\Exception $exception, ExceptionMappingInterface $mapping)
    {
        if (!$this->supportsException($exception)) {
            return null;
        }

        $errorMessage = $mapping->getErrorMessage();
        if (empty($errorMessage)) {
            $errorMessage = $exception->getMessage();
        }

        $formErrors = $exception->getFormErrors();
        $extendedMessage = array(
            'global_errors' => $formErrors['form_errors'],
            'property_errors' => $formErrors['field_errors'],
        );

        return new Error($mapping->getHttpStatusCode(), $mapping->getErrorCode(), $errorMessage,
            $extendedMessage, $mapping->getErrorMoreInfoUrl());
    }

    /**
     * {@inheritDoc}
     */
    public function supportsException(\Exception $exception)
    {
        return $exception instanceof FormErrorException;
    }
}
