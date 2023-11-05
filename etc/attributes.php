<?php

/**
 * Part of unicorn project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

use Windwalker\Console\CommandWrapper;
use Windwalker\Core\Attributes\Controller;
use Windwalker\Core\Attributes\Csrf;
use Windwalker\Core\Attributes\Json;
use Windwalker\Core\Attributes\JsonApi;
use Windwalker\Core\Attributes\Method;
use Windwalker\Core\Attributes\Module;
use Windwalker\Core\Attributes\Ref;
use Windwalker\Core\Attributes\ViewModel;
use Windwalker\Attributes\AttributeType;
use Windwalker\DI\Container;

return [
    // Declaration
    ...Container::getDefaultAttributes(),
    Ref::class => AttributeType::PARAMETERS,

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
];
