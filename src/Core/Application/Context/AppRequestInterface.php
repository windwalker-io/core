<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application\Context;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Data\Collection;
use Windwalker\Event\EventAwareInterface;

/**
 * Interface AppRequestInterface
 */
interface AppRequestInterface extends EventAwareInterface
{
    public function getClientIP(): string;

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface;

    public function getQueryValues(): array;

    /**
     * @return array
     */
    public function getUrlVars(): array;

    /**
     * @return array
     */
    public function getInput(): array;

    public function getHeader(string $name): string;

    /**
     * @param  mixed  ...$fields
     *
     * @return  mixed|Collection
     */
    public function input(...$fields): mixed;

    /**
     * @return SystemUri
     */
    public function getSystemUri(): SystemUri;

    /**
     * @return Route|null
     */
    public function getMatchedRoute(): ?Route;

    /**
     * @return ProxyResolver
     */
    public function getProxyResolver(): ProxyResolver;

    public function getServerRequest(): ServerRequestInterface;
}
