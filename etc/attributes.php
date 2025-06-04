<?php

declare(strict_types=1);

use Windwalker\Console\CommandWrapper;
use Windwalker\Core\Attributes\Assert;
use Windwalker\Core\Attributes\Controller;
use Windwalker\Core\Attributes\Csrf;
use Windwalker\Core\Attributes\Filter;
use Windwalker\Core\Attributes\Json;
use Windwalker\Core\Attributes\JsonApi;
use Windwalker\Core\Attributes\Method;
use Windwalker\Core\Attributes\Module;
use Windwalker\Core\Attributes\Ref;
use Windwalker\Core\Attributes\Request\BodyValue;
use Windwalker\Core\Attributes\Request\File;
use Windwalker\Core\Attributes\Request\Header;
use Windwalker\Core\Attributes\Request\Input;
use Windwalker\Core\Attributes\Request\QueryValue;
use Windwalker\Core\Attributes\Request\RequestInput;
use Windwalker\Core\Attributes\Request\UrlVar;
use Windwalker\Core\Attributes\ViewModel;
use Windwalker\Attributes\AttributeType;
use Windwalker\DI\Container;

return [
    // Declaration
    ...Container::getDefaultAttributes(),
    Ref::class => AttributeType::PARAMETERS,
    Assert::class => AttributeType::PARAMETERS | AttributeType::PROPERTIES,
    Filter::class => AttributeType::PARAMETERS | AttributeType::PROPERTIES,

    // Decorators
    Module::class => AttributeType::CLASSES,
    Controller::class => AttributeType::CLASSES,
    ViewModel::class => AttributeType::CLASSES,
    CommandWrapper::class => AttributeType::CLASSES | AttributeType::CALLABLE,

    // Middleware
    Csrf::class => AttributeType::CALLABLE,
    Json::class => AttributeType::CALLABLE,
    JsonApi::class => AttributeType::CALLABLE,
    Method::class => AttributeType::CALLABLE,

    // Request
    RequestInput::class => AttributeType::PROPERTIES | AttributeType::PARAMETERS,
    Input::class => AttributeType::PROPERTIES | AttributeType::PARAMETERS,
    BodyValue::class => AttributeType::PROPERTIES | AttributeType::PARAMETERS,
    Header::class => AttributeType::PROPERTIES | AttributeType::PARAMETERS,
    QueryValue::class => AttributeType::PROPERTIES | AttributeType::PARAMETERS,
    UrlVar::class => AttributeType::PROPERTIES | AttributeType::PARAMETERS,
    File::class => AttributeType::PROPERTIES | AttributeType::PARAMETERS,
];
