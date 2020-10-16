<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareTrait;

/**
 * The DelegatingController class.
 */
class DelegatingController implements ControllerInterface
{
    /**
     * DelegatingController constructor.
     *
     * @param  Container  $container
     * @param  object     $controller
     */
    public function __construct(protected Container $container, protected object $controller)
    {
        //
    }

    /**
     * execute
     *
     * @param  string  $task
     * @param  array   $args
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function execute(string $task, array $args = []): mixed
    {
        if (!method_exists($this->controller, $task)) {
            throw new \LogicException(
                sprintf(
                    'Method: %s::%s() not found.',
                    $this->controller::class,
                    $task
                )
            );
        }

        return $this->container->call([$this->controller, $task], $args);
    }
}
