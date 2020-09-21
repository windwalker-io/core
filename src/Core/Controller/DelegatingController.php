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
    use ContainerAwareTrait;

    protected object $controller;

    /**
     * DelegatingController constructor.
     *
     * @param  Container  $container
     * @param  object     $controller
     */
    public function __construct(Container $container, object $controller)
    {
        $this->controller = $controller;
        $this->container = $container;
    }

    /**
     * execute
     *
     * @param  ServerRequestInterface  $request
     * @param  string                  $task
     *
     * @return  mixed|ResponseInterface
     * @throws \ReflectionException
     */
    public function execute(ServerRequestInterface $request, string $task)
    {
        $attributes = $request->getAttributes();

        return $this->getContainer()->call([$this, $task], $attributes);
    }
}
