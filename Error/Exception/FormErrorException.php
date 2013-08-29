<?php

/**
 * This file is part of TbbcRestUtilBundle
 *
 * (c) The Big Brains Company <contact@thebigbrainscompany.org>
 *
 */

namespace Tbbc\RestUtilBundle\Error\Exception;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @author Boris Guéry <guery.b@gmail.com>
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 */
class FormErrorException extends \InvalidArgumentException
{
    private $translator;
    private $formErrors;

    public function __construct(FormInterface $form, TranslatorInterface $translator = null,
                                $message = 'An error has occurred while processing your request, make sure your data are valid',
                                $code = 400, \Exception $previous = null)
    {
        $this->translator = $translator;
        $this->buildErrorsTree($form);

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getFormErrors()
    {
        return $this->formErrors;
    }

    /**
     * @param FormInterface $form
     */
    private function buildErrorsTree(FormInterface $form)
    {
        $this->formErrors = array();
        $translator = $this->translator;

        $this->formErrors['form_errors'] = array();
        foreach ($form->getErrors() as $error) {
            /** @var $error FormError */
            $message = $error->getMessage();
            if ($translator) {
                /** @Ignore */
                $message = $translator->trans($message, $error->getMessageParameters(), 'validators');
            }
            array_push($this->formErrors['form_errors'], $message);
        }

        $this->formErrors['field_errors'] = array();
        foreach ($form->all() as $key => $child) {
            /** @var $error FormError */
            foreach ($child->getErrors() as $error) {
                $message = $error->getMessage();
                if ($translator) {
                    /** @Ignore */
                    $message = $translator->trans($message, $error->getMessageParameters(), 'validators');
                }
                $this->formErrors['field_errors'][$key][] = $message;
            }
        }
    }
}
