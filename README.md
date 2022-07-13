The Big Brains Company - TbbcRestUtilBundle
==============
[![Build Status](https://travis-ci.org/TheBigBrainsCompany/TbbcRestUtilBundle.png?branch=master)](https://travis-ci.org/TheBigBrainsCompany/TbbcRestUtilBundle)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/TheBigBrainsCompany/TbbcRestUtilBundle/badges/quality-score.png?s=802ae6f0f19e5a90b9fcd6e5ae512943eeb40912)](https://scrutinizer-ci.com/g/TheBigBrainsCompany/TbbcRestUtilBundle/)

A bundle for integrating [tbbc/rest-util](https://github.com/TheBigBrainsCompany/rest-util) lib in a Symfony application

Table of contents
-----------------

1. [Installation](#installation)
2. [Quick start](#quick-start)
3. [Usage](#usage)
4. [Run the test](#run-the-test)
5. [Contributing](#contributing)
6. [Requirements](#requirements)
7. [Authors](#authors)
8. [License](#license)


Installation
------------

Using [Composer](http://getcomposer.org/), just `$ composer require tbbc/rest-util-bundle` package or:

```json
{
  "require": {
    "tbbc/rest-util-bundle": "@stable"
  }
}
```

Quick start
-----------

### Handling errors in a REST(ful) API

#### Configuration

```yaml
tbbc_rest_util:
    error:
        use_bundled_factories: true
        exception_mapping:
            FormErrorException:
                class: "Tbbc\\RestUtilBundle\\Error\\Exception\\FormErrorException"
                factory: tbbc_rest_util_form_error
                http_status_code: 400
                error_code: 400101
                error_message: "Invalid input"
                error_more_info_url: "http://api.my.tld/doc/error/400101"
            AccessDeniedException:
                class: "Symfony\\Component\\Security\\Core\\AccessDeniedException"
                factory: default
                http_status_code: 401
                error_code: 401001
                error_message: "Access denied"
                extended_message: "The given token don't have enough privileges for accessing to this resource"
                error_more_info_url: "http://api.my.tld/doc/error/401001"
            CustomException:
                class: "My\\ApiBundle\\Exception\\CustomException"
                factory: my_api_custom
                http_status_code: 501
                error_code: 501001
                error_more_info_url: "http://api.my.tld/doc/error/501001"
            Exception:
                class: "\\Exception"
                factory: default
                http_status_code: 500
                error_code: 501203
                error_message: "Server error"
```

#### Custom Symfony Exception Listener

```php
<?php

namespace My\ApiBundle\EventListener;

// ... other use statements
use Tbbc\RestUtil\Error\ErrorResolverInterface;

class RestExceptionListener extends ExceptionListener
{
    private $errorResolver;

    public function __construct(ErrorResolverInterface $errorResolver, $controller, LoggerInterface $logger = null)
    {
        $this->errorResolver = $errorResolver;
        parent::__construct($controller, $logger);
    }

    /**
     * @param GetResponseForExceptionEvent $event
     * @return void
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        static $handling;

        if (true === $handling) {
            return;
        }

        $exception = $event->getException();
        $error = $this->errorResolver->resolve($exception);
        if (null == $error) {
            return;
        }

        $handling = true;

        $response = new Response(json_encode($error->toArray()), $error->getHttpStatusCode(), array(
            'Content-Type' => 'application/json'
        ));

        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => array('onKernelException', 10),
        );
    }
}
```

```xml
<?xml version="1.0" ?>

<container xmlns="http://symfony.com/schema/dic/services"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <parameters>
        <parameter key="my_api.event_listener.rest_exception.class">My\ApiBundle\EventListener\RestExceptionListener</parameter>
    </parameters>

    <services>
        <service id="my_api.event_listener.rest_exception" class="%my_api.event_listener.rest_exception.class%">
            <tag name="kernel.event_subscriber" />
            <tag name="monolog.logger" channel="request" />
            <argument type="service" id="tbbc_rest_util.error.error_resolver" />
            <argument>%twig.exception_listener.controller%</argument>
            <argument type="service" id="logger" on-invalid="null" />
        </service>
    </services>
</container>
```

#### Api Controller code

```php
<?php

namespace My\ApiBundle\Controller;

// ... other use statements
use Symfony\Component\Security\Core\AccessDeniedException;
use Tbbc\RestUtilBundle\Error\Exception\FormErrorException;
use My\ApiBundle\Exception\CustomException;

class PostCommentsController extends Controller
{
    public function postCommentsAction($postId)
    {
        // ... fetch $post with $postId

        if (!$this->get('security.context')->isGranted('COMMENT', $post)) {
            throw new AccessDeniedException('Access denied');
        }

        $commentResource = new CommentResource();
        $form = $this->createNamedForm('', new CommentResourceType(), $commentResource);
        $form->bind($this->getRequest());

        if (!$form->isValid()) {
            throw new FormErrorException($form);
        }

        // another error
        if (....) {
            throw new CustomException('Something bad just happened!');
        }

        // ... save comment or whatever
    }
}
```

#### MyApiCustomException error factory

```php
<?php

namespace My\ApiBundle\Error\Factory;

use Tbbc\RestUtil\Error\Error;
use Tbbc\RestUtil\Error\ErrorFactoryInterface;
use Tbbc\RestUtil\Error\Mapping\ExceptionMappingInterface;
use My\ApiBundle\Exception\CustomException;

class CustomErrorFactory implements ErrorFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        return 'my_api_custom';
    }

    /**
     * {@inheritDoc}
     */
    public function createError(\Throwable $exception, ExceptionMappingInterface $mapping)
    {
        if (!$this->supportsException($exception)) {
            return null;
        }

        $errorMessage = $mapping->getErrorMessage();
        if (empty($errorMessage)) {
            $errorMessage = $exception->getMessage();
        }

        $extendedMessage = $exception->getMoreExtendedMessage();
        // Or whatever you need to do with your exception here

        return new Error($mapping->getHttpStatusCode(), $mapping->getErrorCode(), $errorMessage,
            $extendedMessage, $mapping->getErrorMoreInfoUrl());
    }

    /**
     * {@inheritDoc}
     */
    public function supportsException(\Throwable $exception)
    {
        return $exception instanceof CustomException;
    }
}
```

```xml
<?xml version="1.0" ?>

<container xmlns="http://symfony.com/schema/dic/services"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">
    <parameters>
        <parameter key="my_api.error.custom_error_factory.class">My\ApiBundle\Error\Factory\CustomErrorFactory</parameter>
    </parameters>

    <services>
        <service id="my_api.error.custom_error_factory" class="%my_api.error.custom_error_factory.class%">
            <tag name="tbbc_rest_util.error_factory" />
        </service>
    </services>
</container>
```

**ENJOY!**

For the exceptions thrown in the previous `PostCommentsController` class example, the response body will be respectively
something like the following:

For the **AccessDeniedException** exception:

```json
{
    "http_status_code": 401,
    "code": 401001,
    "message": "Access denied",
    "extended_message": "The given token don't have enough privileges for accessing to this resource",
    "more_info_url": "http:\/\/api.my.tld\/doc\/error\/401001"
}
```

For the **FormErrorException** exception:

```json
{
    "http_status_code": 400,
    "code": 400101,
    "message": "Invalid input",
    "extended_message": {
        "global_errors": [
            "Bubbled form error!"
        ],
        "property_errors": {
            "content": [
                "The comment content should not be blank",
            ]
        }
    },
    "more_info_url": "http:\/\/api.my.tld\/doc\/error\/400101"
}
```

For the **CustomException** exception:

```json
{
    "http_status_code": 501,
    "code": 501001,
    "message": "Something bad just happened!",
    "extended_message": null,
    "more_info_url": "http:\/\/api.my.tld\/doc\/error\/501001"
}
```

Usage
-----

Run the test
------------

First make sure you have installed all the dependencies, run:

`$ composer install --dev`

then, run the test from within the root directory:

`$ vendor/bin/phpunit`

Contributing
------------

1. Take a look at the [list of issues](http://github.com/TheBigBrainsCompany/TbbcRestUtilBundle/issues).
2. Fork
3. Write a test (for either new feature or bug)
4. Make a PR

Requirements
------------

* PHP 5.3+

Authors
-------

* Benjamin Dulau - benjamin.dulau@gmail.com
* Valentin Ferriere - valentin@v-labs.fr

License
-------

`The Big Brains Company - TbbcRestUtilBundle` is licensed under the MIT License - see the LICENSE file for details

[![The Big Brains Company - Logo](http://tbbc-valid.thebigbrainscompany.com/assets/images/logo-tbbc.png)](http://thebigbrainscompany.com)
