<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\NoReturn;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Data\Collection;
use Windwalker\DI\Container;
use Windwalker\DI\Parameters;
use Windwalker\Filter\Traits\FilterAwareTrait;
use Windwalker\Uri\Uri;

/**
 * The Context class.
 */
#[Immutable]
class AppContext implements WebApplicationInterface
{
    use WebApplicationTrait;
    use FilterAwareTrait;

    /**
     * @var callable|array
     */
    protected mixed $controller = null;

    protected ?Collection $state = null;

    protected ?AppRequest $appRequest = null;

    protected bool $isDebug = false;

    protected string $mode = '';

    protected ?Parameters $params = null;

    /**
     * Context constructor.
     *
     * @param  Container  $container
     */
    public function __construct(Container $container)
    {
        $this->state     = new Collection();
        $this->container = $container;
    }

    public function getRootApp(): WebApplication
    {
        return $this->container->get(WebApplication::class);
    }

    public function config(string $name, ?string $delimiter = '.'): mixed
    {
        return $this->getContainer()->getParam($name, $delimiter);
    }

    public function to(string $name, array $args = [], int $options = Navigator::TYPE_PATH): RouteUri
    {
        return $this->container->get(Navigator::class)->to(
            $name,
            $args,
            $options
        );
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
    public function withController(mixed $controller): static
    {
        $new             = clone $this;
        $new->controller = $controller;

        return $new;
    }

    public function getRequestMethod(): string
    {
        return $this->appRequest->getMethod();
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
    public function withUrlVars(array $vars): static
    {
        $new             = clone $this;
        $new->appRequest = $this->appRequest->withUrlVars($vars);

        return $new;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    /**
     * @param  bool  $isDebug
     *
     * @return  static  Return self to support chaining.
     */
    public function withIsDebug(bool $isDebug): static
    {
        $new          = clone $this;
        $new->isDebug = $isDebug;

        return $new;
    }

    /**
     * @return Parameters|null
     */
    public function getParams(): ?Parameters
    {
        return $this->params;
    }

    /**
     * @param  Parameters|null  $params
     *
     * @return  static  Return self to support chaining.
     */
    public function withParams(?Parameters $params): static
    {
        $new         = clone $this;
        $new->params = $params;

        return $new;
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
    public function withContainer(Container $container): static
    {
        $new            = clone $this;
        $new->container = $container;

        return $new;
    }

    /**
     * @return Uri
     */
    public function getUri(): Uri
    {
        return $this->appRequest->getUri();
    }

    /**
     * @param  UriInterface|null  $uri
     *
     * @return  static  Return self to support chaining.
     */
    public function withUri(UriInterface $uri): static
    {
        $new             = clone $this;
        $new->appRequest = $new->appRequest->withUri($uri);

        return $new;
    }

    /**
     * @return SystemUri|null
     */
    public function getSystemUri(): ?SystemUri
    {
        return $this->appRequest->getSystemUri();
    }

    /**
     * @param  SystemUri  $uri
     *
     * @return  static  Return self to support chaining.
     */
    public function withSystemUri(SystemUri $uri): static
    {
        $new             = clone $this;
        $new->appRequest = $this->appRequest->withSystemUri($uri);

        return $new;
    }

    /**
     * @return Collection|null
     */
    public function getState(): ?Collection
    {
        return $this->state;
    }

    /**
     * @param  Collection|null  $state
     *
     * @return  static  Return self to support chaining.
     */
    public function withState(?Collection $state): static
    {
        $new        = clone $this;
        $new->state = $state;

        return $new;
    }

    /**
     * @return AppRequest
     */
    public function getAppRequest(): AppRequest
    {
        return $this->appRequest;
    }

    /**
     * @param  AppRequest  $request
     *
     * @return  static  Return self to support chaining.
     */
    public function withAppRequest(AppRequest $request): static
    {
        $new             = clone $this;
        $new->appRequest = $request;

        return $new;
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
    public function withMatchedRoute(?Route $matchedRoute): static
    {
        $new             = clone $this;
        $new->appRequest = $this->appRequest->withMatchedRoute($matchedRoute);

        return $new;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @param  string  $mode
     *
     * @return  static  Return self to support chaining.
     */
    public function withMode(string $mode): static
    {
        $new       = clone $this;
        $new->mode = $mode;

        return $new;
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
     * inputWithMethod
     *
     * @param  string  $method
     * @param  mixed   ...$fields
     *
     * @return  mixed|Collection
     */
    public function inputWithMethod(string $method = 'REQUEST', ...$fields): mixed
    {
        return $this->appRequest->inputWithMethod($method, ...$fields);
    }

    /**
     * file
     *
     * @param  mixed  ...$fields
     *
     * @return  UploadedFileInterface[]|UploadedFileInterface|array
     */
    public function file(...$fields): mixed
    {
        return $this->appRequest->file(...$fields);
    }

    /**
     * Redirect to another URL.
     *
     * @param  string|\Stringable  $url
     * @param  int                 $code
     * @param  bool                $instant
     *
     * @return  ResponseInterface
     */
    public function redirect(string|\Stringable $url, int $code = 303, bool $instant = false): ResponseInterface
    {
        $systemUri = $this->getSystemUri();

        if ($systemUri) {
            $url = $systemUri->absolute((string) $url);
        }

        return $this->getRootApp()->redirect($url, $code, $instant);
    }

    /**
     * Close this request.
     *
     * @param  mixed  $return
     *
     * @return  void
     */
    #[NoReturn]
    public function close(
        mixed $return = ''
    ): void {
        $this->getRootApp()->close($return);
    }
}
