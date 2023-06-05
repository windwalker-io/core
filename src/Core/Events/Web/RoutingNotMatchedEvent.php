<?php

/**
 * Part of datavideo project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Event\AbstractEvent;

/**
 * The RoutingNotMatchedEvent class.
 */
class RoutingNotMatchedEvent extends AbstractEvent
{
    protected SystemUri $systemUri;

    protected ServerRequestInterface $request;

    protected string $route;

    protected RouteNotFoundException $exception;

    /**
     * @return SystemUri
     */
    public function getSystemUri(): SystemUri
    {
        return $this->systemUri;
    }

    /**
     * @param  SystemUri  $systemUri
     *
     * @return  static  Return self to support chaining.
     */
    public function setSystemUri(SystemUri $systemUri): static
    {
        $this->systemUri = $systemUri;

        return $this;
    }

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
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @param  string  $route
     *
     * @return  static  Return self to support chaining.
     */
    public function setRoute(string $route): static
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @return RouteNotFoundException
     */
    public function getException(): RouteNotFoundException
    {
        return $this->exception;
    }

    /**
     * @param  RouteNotFoundException  $exception
     *
     * @return  static  Return self to support chaining.
     */
    public function setException(RouteNotFoundException $exception): static
    {
        $this->exception = $exception;

        return $this;
    }
}
