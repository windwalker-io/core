<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Controller;

use Windwalker\DI\Attributes\Inject;
use Windwalker\DI\Container;

/**
 * Trait ClassDecorator
 */
class ControllerDecorator
{
    protected ?object $innerController = null;

    #[Inject]
    protected ?Container $container = null;

    /**
     * DecoratorTrait constructor.
     *
     * @param  object  $innerObject
     */
    public function __construct(object $innerObject)
    {
        $this->innerController = $innerObject;
    }

    protected function delegate(string $task, ...$args): mixed
    {
        return $this->container->call([$this->innerController, $task], $args);
    }

    public function __call(string $name, array $args): mixed
    {
        return $this->innerController->$name(...$args);
    }
}
