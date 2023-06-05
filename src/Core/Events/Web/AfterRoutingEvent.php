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
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Event\AbstractEvent;

/**
 * The BeforeRoutingEvent class.
 */
class AfterRoutingEvent extends AbstractEvent
{
    protected SystemUri $systemUri;

    protected ServerRequestInterface $request;

    protected string $route;

    protected Route $matched;

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
     * @return Route
     */
    public function getMatched(): Route
    {
        return $this->matched;
    }

    /**
     * @param  Route  $matched
     *
     * @return  static  Return self to support chaining.
     */
    public function setMatched(Route $matched): static
    {
        $this->matched = $matched;

        return $this;
    }
}
