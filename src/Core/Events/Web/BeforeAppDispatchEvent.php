<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\DI\Container;
use Windwalker\Event\AbstractEvent;

/**
 * The AppBeforeExecute class.
 */
class BeforeAppDispatchEvent extends AbstractEvent
{
    protected ServerRequestInterface $request;

    protected iterable $middlewares = [];

    protected Container $container;

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @param  ServerRequestInterface  $request
     *
     * @return  static  Return self to support chaining.
     */
    public function setRequest(ServerRequestInterface $request): static
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @param  Container  $container
     *
     * @return  static  Return self to support chaining.
     */
    public function setContainer(Container $container): static
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @return iterable
     */
    public function getMiddlewares(): iterable
    {
        return $this->middlewares;
    }

    /**
     * @param  iterable  $middlewares
     *
     * @return  static  Return self to support chaining.
     */
    public function setMiddlewares(iterable $middlewares): static
    {
        $this->middlewares = $middlewares;

        return $this;
    }
}
