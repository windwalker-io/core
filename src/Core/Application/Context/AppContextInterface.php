<?php

declare(strict_types=1);

namespace Windwalker\Core\Application\Context;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\RootApplicationInterface;
use Windwalker\Core\Router\Route;
use Windwalker\Core\State\AppState;
use Windwalker\Data\Collection;

/**
 * Interface RequestAppContextInterface
 */
interface AppContextInterface extends ApplicationInterface
{
    public function getRootApp(): RootApplicationInterface;

    /**
     * @return array|callable
     */
    public function getController(): mixed;

    /**
     * @param  array|callable  $controller
     *
     * @return  static  Return self to support chaining.
     */
    public function setController(mixed $controller): static;

    /**
     * @param  string|null  $prefix
     *
     * @return AppState
     */
    public function getState(?string $prefix = null): AppState;

    public function state(string $name, mixed $driver = null): mixed;

    /**
     * @param  AppState  $state
     *
     * @return  static  Return self to support chaining.
     */
    public function setState(AppState $state): static;

    public function getSubState(string $prefix): AppState;

    public function getServerRequest(): ServerRequestInterface;

    /**
     * @return Route|null
     */
    public function getMatchedRoute(): ?Route;

    /**
     * @param  Route|null  $matchedRoute
     *
     * @return  static  Return self to support chaining.
     */
    public function setMatchedRoute(?Route $matchedRoute): static;

    public function getHeader(string $name): string;

    /**
     * input
     *
     * @param  mixed  ...$fields
     *
     * @return  mixed|Collection
     */
    public function input(...$fields): mixed;
}
