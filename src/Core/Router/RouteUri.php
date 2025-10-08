<?php

declare(strict_types=1);

namespace Windwalker\Core\Router;

use Closure;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\UuidInterface;
use Stringable;
use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Core\Utilities\Base64Url;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Uri\Uri;
use Windwalker\Uri\UriHelper;
use Windwalker\Utilities\Arr;

use function Windwalker\unwrap_enum;

/**
 * The RouteUri class.
 */
class RouteUri extends Uri implements NavConstantInterface
{
    /**
     * @var callable
     */
    protected $handler;

    protected array|null $handledData = null;

    protected NavOptions $options;

    protected int $status = 303;

    protected ?string $cache = null;

    /**
     * RouteUri constructor.
     *
     * @param  Closure|Stringable|string  $uri
     * @param  array|null                 $vars
     * @param  Navigator                  $navigator
     * @param  NavOptions|int             $options
     */
    public function __construct(
        mixed $uri,
        ?array $vars,
        protected Navigator $navigator,
        NavOptions|int $options = new NavOptions()
    ) {
        if ($uri instanceof Closure) {
            $this->handler = $uri;
            $uri = '';
        }

        parent::__construct((string) $uri);

        if ($vars !== null) {
            $this->vars = array_merge($this->vars, $vars ?? []);
            $this->query = UriHelper::buildQuery($this->vars);
        }

        $this->options = $options;
    }

    /**
     * @param  int|NavOptions  $options
     *
     * @return  $this
     */
    public function options(int|NavOptions $options): static
    {
        $new = clone $this;
        $new->options = $this->mergeDefaultOptions($options);

        return $new;
    }

    /**
     * full
     *
     * @return  static
     */
    public function full(): static
    {
        return $this->options(new NavOptions(mode: NavMode::FULL));
    }

    /**
     * path
     *
     * @return  static
     */
    public function path(): static
    {
        return $this->options(new NavOptions(mode: NavMode::PATH));
    }

    /**
     * raw
     *
     * @return  static
     */
    public function raw(): static
    {
        return $this->options(Navigator::TYPE_RAW);
    }

    /**
     * id
     *
     * @param  int  $id
     *
     * @return  static
     */
    public function id(mixed $id): static
    {
        return $this->var('id', $id);
    }

    /**
     * alias
     *
     * @param  string  $alias
     *
     * @return  static
     */
    public function alias(string $alias): static
    {
        return $this->var('alias', $alias);
    }

    /**
     * page
     *
     * @param  int  $page
     *
     * @return  static
     */
    public function page(int $page): static
    {
        return $this->var('page', $page);
    }

    /**
     * layout
     *
     * @param  string  $layout
     *
     * @return  static
     *
     * @since  3.5.8
     */
    public function layout(string $layout): static
    {
        return $this->var('layout', $layout);
    }

    /**
     * task
     *
     * @param  string  $task
     *
     * @return  static
     *
     * @since  3.5.8
     */
    public function task(string $task): static
    {
        return $this->var('task', $task);
    }

    /**
     * addVar
     *
     * @param  string  $name
     * @param  mixed   $value
     *
     * @return  $this
     */
    public function var(string $name, mixed $value): static
    {
        if ($value instanceof UuidInterface) {
            $value = (string) $value;
        }

        return $this->withVar($name, unwrap_enum($value));
    }

    public function withVar(string $name, mixed $value): static
    {
        $new = parent::withVar($name, $value);

        if ($this->options->allowQuery === false || is_array($this->options->allowQuery)) {
            $new = $new->allowQuery([$name], true);
        }

        return $new;
    }

    /**
     * delVar
     *
     * @param  string  $name
     *
     * @return  static
     *
     * @since  3.5.5
     */
    public function delVar(string $name): static
    {
        return $this->withoutVar($name);
    }

    public function withReturn(string $var = 'return', mixed $return = null): static
    {
        $return ??= $this->navigator->getAppContext()->getSystemUri()->full();

        $return = Uri::wrap($return)->withoutVar('return');

        $returnBase64 = Base64Url::encode((string) $return);

        return $this->var($var, $returnBase64);
    }

    /**
     * Method to get property Escape
     *
     * @return  bool
     */
    public function isEscape(): bool
    {
        return (bool) $this->options->escape;
    }

    /**
     * escape
     *
     * @param  bool  $bool
     *
     * @return  $this
     */
    public function escape(bool $bool = true): static
    {
        return $this->options(new NavOptions(escape: $bool));
    }

    /**
     * Method to get property Mute
     *
     * @return  bool
     */
    public function isMute(): bool
    {
        return (bool) $this->options->mute;
    }

    /**
     * Method to set property mute
     *
     * @param  bool  $mute
     *
     * @return  static  Return self to support chaining.
     */
    public function mute(bool $mute = true): static
    {
        return $this->options(new NavOptions(mute: $mute));
    }

    protected function handleError(RouteNotFoundException $e): string
    {
        $options = $this->computedOptions();

        if (!($options->mute)) {
            throw $e;
        }

        if ($options->debugAlert) {
            return "javascript: alert('{$e->getMessage()}');";
        }

        return '#';
    }

    public function computedOptions(): NavOptions
    {
        return $this->options->defaults($this->navigator->getOptions());
    }

    /**
     * @return NavOptions
     */
    public function getOptions(): NavOptions
    {
        return $this->options;
    }

    public function go(int $code = 303, int $options = 0): ?ResponseInterface
    {
        return $this->navigator->redirect($this, $code, $options);
    }

    #[ArrayShape(['string', 'array', Route::class])]
    public function getHandledData(): array
    {
        return $this->handledData ??= ($this->handler)($this->vars);
    }

    public function cacheReset(): static
    {
        $this->handledData = null;

        return $this;
    }

    /**
     * Magic method to get the string representation of the URI object.
     *
     * @return  string
     *
     * @since   2.0
     */
    public function __toString(): string
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if ($this->handler instanceof Closure) {
            $vars = [];

            try {
                [$uri, $vars] = $this->getHandledData();

                $uri = new Uri($uri);
            } catch (RouteNotFoundException $e) {
                return $this->handleError($e);
            }

            if ($this->scheme) {
                $uri->scheme = $this->scheme;
            }

            if ($this->host) {
                $uri->host = $this->host;
            }

            if ($this->user) {
                $uri->user = $this->user;
            }

            if ($this->pass) {
                $uri->pass = $this->pass;
            }

            if ($this->port) {
                $uri->port = $this->port;
            }

            if ($vars !== []) {
                if ($this->options->allowQuery === false) {
                    $vars = [];
                } elseif (is_array($this->options->allowQuery)) {
                    $vars = Arr::only($vars, $this->options->allowQuery);
                }

                foreach ($vars as $key => $var) {
                    $uri = $uri->withVar($key, $var);
                }
            }
        } else {
            $uri = new Uri(parent::__toString());

            if ($this->options->allowQuery === false) {
                $uri = $uri->withQuery('');
            } elseif (is_array($this->options->allowQuery)) {
                $uri = $uri->withQueryParams(
                    Arr::only($uri->getQueryValues(), $this->options->allowQuery)
                );
            }
        }

        $uri = ltrim((string) $uri, '/');

        $options = $this->computedOptions();

        if ($options->mode === NavMode::RAW) {
            return $this->cache = $uri;
        }

        if ($options->mode === NavMode::FULL) {
            return $this->cache = $this->navigator->absolute($uri, $options);
        }

        return $this->cache = $this->navigator->absolute($uri, $options);
    }

    public function withMessage(string|array $messages, string $type = 'info'): static
    {
        $this->navigator->getAppContext()->addMessage($messages, $type);

        return $this;
    }

    public function allowQuery(array|bool|null $fields, bool $replace = false): static
    {
        $new = clone $this;

        if (is_array($fields)) {
            $fields = array_values($fields);
        }

        if ($replace || is_bool($fields)) {
            $new->options->allowQuery = $fields;
        } else {
            if ($new->options->allowQuery === false) {
                $new->options->allowQuery = [];
            }

            $new->options->allowQuery = array_merge(
                $new->options->allowQuery ?? [],
                array_values((array) $fields)
            );
        }

        return $new;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param  int  $status
     *
     * @return  static  Return self to support chaining.
     */
    public function withStatus(int $status): static
    {
        $new = clone $this;
        $new->status = $status;

        return $new;
    }

    public function toResponse(?int $status = null, array $headers = []): RedirectResponse
    {
        return new RedirectResponse($this, $status ?? $this->status, $headers);
    }

    /**
     * @inheritDoc
     */
    public function __clone(): void
    {
        $this->handledData = null;
        $this->cache = null;
        $this->options = clone $this->options;
    }

    public function resetCache(): void
    {
        $this->cache = null;
    }

    /**
     * @param  NavOptions|int  $options
     *
     * @return  NavOptions
     */
    protected function mergeDefaultOptions(NavOptions|int $options): NavOptions
    {
        return NavOptions::wrapWith($options)->defaults($this->options);
    }
}
