<?php

declare(strict_types=1);

namespace Windwalker\Core\Application\Context;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\RootApplicationInterface;
use Windwalker\Core\Application\WebApplicationTrait;
use Windwalker\Core\Router\Route;
use Windwalker\Core\State\AppState;
use Windwalker\Data\Collection;
use Windwalker\DI\Parameters;

/**
 * Trait RequestAppContextTrait
 */
trait AppContextTrait
{
    use WebApplicationTrait {
        __get as magicGet;
    }

    /**
     * @var callable|array
     */
    protected mixed $controller = null;

    protected AppState $state;

    protected bool $isDebug = false;

    protected string $mode = '';

    protected ?Parameters $params = null;

    public function getRootApp(): RootApplicationInterface
    {
        return $this->container->get(RootApplicationInterface::class);
    }

    /**
     * @return array|callable
     */
    public function getController(): mixed
    {
        return $this->controller;
    }

    /**
     * @param  array|callable  $controller
     *
     * @return  static  Return self to support chaining.
     */
    public function setController(mixed $controller): static
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @param  string|null  $prefix
     *
     * @return AppState
     */
    public function getState(?string $prefix = null): AppState
    {
        $state = $this->state;

        if ($prefix) {
            $state = $state->withPrefix($prefix);
        }

        return $state;
    }

    public function state(string $name, mixed $driver = null): mixed
    {
        return $this->getState()->get($name, $driver);
    }

    /**
     * @param  AppState  $state
     *
     * @return  static  Return self to support chaining.
     */
    public function setState(AppState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getSubState(string $prefix): AppState
    {
        return $this->state->withPrefix($prefix);
    }

    public function getServerRequest(): ServerRequestInterface
    {
        return $this->appRequest->getRequest();
    }

    /**
     * @return Route|null
     */
    public function getMatchedRoute(): ?Route
    {
        return $this->appRequest->getMatchedRoute();
    }

    /**
     * @param  Route|null  $matchedRoute
     *
     * @return  static  Return self to support chaining.
     */
    public function setMatchedRoute(?Route $matchedRoute): static
    {
        $this->appRequest = $this->appRequest->withMatchedRoute($matchedRoute);

        return $this;
    }

    public function getQueryValues(): array
    {
        return $this->appRequest->getQueryValues();
    }

    public function getBodyValues(): array
    {
        return $this->appRequest->getBodyValues();
    }

    /**
     * @return array
     */
    public function getUrlVars(): array
    {
        return $this->appRequest->getUrlVars();
    }

    /**
     * @param  array  $vars
     *
     * @return  static  Return self to support chaining.
     */
    public function setUrlVars(array $vars): static
    {
        $this->appRequest = $this->appRequest->withUrlVars($vars);

        return $this;
    }

    public function getHeader(string $name): string
    {
        return $this->appRequest->getHeader($name);
    }

    /**
     * input
     *
     * @param  mixed  ...$fields
     *
     * @return  mixed|Collection
     */
    public function input(...$fields): mixed
    {
        return $this->appRequest->input(...$fields);
    }

    /**
     * @inheritDoc
     */
    public function __get(string $name)
    {
        if ($name === 'state') {
            return $this->$name;
        }

        return $this->magicGet($name);
    }
}
