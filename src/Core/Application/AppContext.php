<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use JetBrains\PhpStorm\ExitPoint;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Data\Collection;
use Windwalker\DI\Container;
use Windwalker\DI\Parameters;
use Windwalker\Filter\Traits\FilterAwareTrait;
use Windwalker\Http\Output\Output;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Http\Uri;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Assert\ArgumentsAssert;

use function Windwalker\collect;

/**
 * The Context class.
 */
class AppContext implements WebApplicationInterface
{
    use WebApplicationTrait;
    use FilterAwareTrait;

    /**
     * @var callable|array
     */
    protected mixed $controller = null;

    protected ?SystemUri $systemUri = null;

    protected ?Route $matchedRoute = null;

    protected ?Collection $state = null;

    protected ?ServerRequestInterface $request = null;

    protected ?array $input = null;

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
        $this->state = new Collection();
        $this->container = $container;
    }

    public function getRootApp(): WebApplication
    {
        return $this->container->get(WebApplication::class);
    }

    public function config(string $name, string $delimiter = '.'): mixed
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
        $new = clone $this;
        $new->controller = $controller;

        return $new;
    }

    public function getQueryValues(): array
    {
        return array_merge(
            $this->getUrlVars(),
            $this->getUri()->getQueryValues()
        );
    }

    /**
     * @return array
     */
    public function getUrlVars(): array
    {
        if (!$this->matchedRoute) {
            return [];
        }

        return $this->matchedRoute->getVars();
    }

    /**
     * @param  array  $vars
     *
     * @return  static  Return self to support chaining.
     */
    public function withUrlVars(array $vars): static
    {
        $new = clone $this;
        $new->matchedRoute = clone $new->matchedRoute;
        $new->matchedRoute->vars($vars);

        return $new;
    }

    /**
     * @return array
     */
    public function getInput(): array
    {
        return $this->input;
    }

    /**
     * @param  array  $input
     *
     * @return  static  Return self to support chaining.
     */
    public function withInput(array $input): static
    {
        $new = clone $this;
        $new->input = $input;

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
        $new = clone $this;
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
        $new = clone $this;
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
        $new = clone $this;
        $new->container = $container;

        return $new;
    }

    /**
     * @return Uri
     */
    public function getUri(): Uri
    {
        return $this->request->getUri();
    }

    /**
     * @param  UriInterface|null  $uri
     *
     * @return  static  Return self to support chaining.
     */
    public function withUri(UriInterface $uri): static
    {
        $new = clone $this;
        $new->request = $new->request->withUri($uri);
        $this->input = null;

        return $new;
    }

    /**
     * @return SystemUri|null
     */
    public function getSystemUri(): ?SystemUri
    {
        return $this->systemUri;
    }

    /**
     * @param  SystemUri  $uri
     *
     * @return  static  Return self to support chaining.
     */
    public function withSystemUri(SystemUri $uri): static
    {
        $new            = clone $this;
        $new->systemUri = $uri;

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
        $new = clone $this;
        $new->state = $state;

        return $new;
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
    public function withRequest(ServerRequestInterface $request): static
    {
        $new = clone $this;
        $new->request = $request;
        $this->input = null;

        return $new;
    }

    /**
     * @return Route|null
     */
    public function getMatchedRoute(): ?Route
    {
        return $this->matchedRoute;
    }

    /**
     * @param  Route|null  $matchedRoute
     *
     * @return  static  Return self to support chaining.
     */
    public function withMatchedRoute(?Route $matchedRoute): static
    {
        $new = clone $this;
        $new->matchedRoute = $matchedRoute;
        $this->input = null;

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
        $new = clone $this;
        $new->mode = $mode;

        return $new;
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
        ArgumentsAssert::assert(
            $fields !== [],
            'Please provide at least 1 field.'
        );

        $input = $this->compileInput();
        $data = [];

        if (!Arr::isAssociative($fields)) {
            foreach ($fields as $field) {
                $data[$field] = $input[$field] ?? null;
            }
        } else {
            foreach (array_keys($fields) as $field) {
                $data[$field] = $input[$field] ?? null;
            }

            $this->getFilterFactory()->createNested($fields)->test($data);
        }

        if (\Windwalker\count($fields) === 1) {
            return array_shift($data);
        }

        return collect($data);
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
        ArgumentsAssert::assert(
            $fields !== [],
            'Please provide at least 1 field.'
        );

        $files = $this->getRequest()->getUploadedFiles();
        $data = [];

        foreach ($fields as $field) {
            $data[$field] = $files[$field] ?? null;
        }

        if (\Windwalker\count($fields) === 1) {
            return array_shift($data);
        }

        return collect($data);
    }

    protected function compileInput(): array
    {
        return $this->input ??= array_merge(
            $this->getQueryValues(),
            $this->getRequest()->getParsedBody()
        );
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
    #[ExitPoint]
    public function close(mixed $return = ''): void
    {
        $this->getRootApp()->close($return);
    }
}
