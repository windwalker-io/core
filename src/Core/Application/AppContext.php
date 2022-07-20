<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\NoReturn;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use ReflectionException;
use Stringable;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\State\AppState;
use Windwalker\Data\Collection;
use Windwalker\DI\Container;
use Windwalker\DI\Parameters;
use Windwalker\Filter\Traits\FilterAwareTrait;
use Windwalker\Session\Session;
use Windwalker\Uri\Uri;

/**
 * The Context class.
 *
 * @property-read AppState $state
 */
#[Immutable(Immutable::PROTECTED_WRITE_SCOPE)]
class AppContext implements WebApplicationInterface
{
    use WebApplicationTrait {
        __get as magicGet;
    }
    use FilterAwareTrait;

    /**
     * @var callable|array
     */
    protected mixed $controller = null;

    protected AppState $state;

    protected ?AppRequest $appRequest = null;

    protected bool $isDebug = false;

    protected string $mode = '';

    protected ?Parameters $params = null;

    /**
     * Context constructor.
     *
     * @param  Container  $container
     *
     * @throws ReflectionException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getRootApp(): WebApplicationInterface
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
    public function setController(mixed $controller): static
    {
        $this->controller = $controller;

        return $this;
    }

    public function getRequestRawMethod(): string
    {
        return $this->appRequest->getMethod();
    }

    public function getRequestMethod(): string
    {
        return $this->appRequest->getOverrideMethod();
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
    public function setParams(?Parameters $params): static
    {
        $this->params = $params;

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
     * @return Uri
     */
    public function getUri(): Uri
    {
        return $this->appRequest->getUri();
    }

    /**
     * @param  UriInterface  $uri
     *
     * @return  static  Return self to support chaining.
     */
    public function sethUri(UriInterface $uri): static
    {
        $this->appRequest = $this->appRequest->withUri($uri);

        return $this;
    }

    /**
     * @return SystemUri
     */
    public function getSystemUri(): SystemUri
    {
        return $this->appRequest->getSystemUri();
    }

    /**
     * @param  SystemUri  $uri
     *
     * @return  static  Return self to support chaining.
     */
    public function setSystemUri(SystemUri $uri): static
    {
        $this->appRequest = $this->appRequest->withSystemUri($uri);

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
    public function setAppRequest(AppRequest $request): static
    {
        $this->appRequest = $request;

        return $this;
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
    public function inputOfMethod(string $method = 'REQUEST', ...$fields): mixed
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
     * @param  string|Stringable  $url
     * @param  int                $code
     * @param  bool               $instant
     *
     * @return  ResponseInterface
     */
    public function redirect(string|Stringable $url, int $code = 303, bool $instant = false): ResponseInterface
    {
        $systemUri = $this->getSystemUri();

        $url = $systemUri->absolute((string) $url);

        return $this->getRootApp()->redirect($url, $code, $instant);
    }

    public function addMessage(string|array $messages, ?string $type = 'info'): static
    {
        $this->service(Session::class)->addFlash($messages, $type ?? 'info');

        return $this;
    }

    public function getStage(): ?string
    {
        $matched = $this->getMatchedRoute();

        if (!$matched) {
            return null;
        }

        return (string) $matched->getExtraValue('namespace');
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
