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

    protected int $options = 0;

    protected int $status = 303;

    protected ?string $cache = null;

    /**
     * RouteUri constructor.
     *
     * @param  Closure|Stringable|string  $uri
     * @param  array|null                 $vars
     * @param  Navigator                  $navigator
     * @param  int                        $options
     */
    public function __construct(mixed $uri, ?array $vars, protected Navigator $navigator, int $options = 0)
    {
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
     * type
     *
     * @param  int  $options
     *
     * @return  $this
     */
    public function options(int $options): static
    {
        $new = clone $this;
        $new->options = $options;

        return $new;
    }

    /**
     * full
     *
     * @return  static
     */
    public function full(): static
    {
        return $this->options(Navigator::TYPE_FULL);
    }

    /**
     * path
     *
     * @return  static
     */
    public function path(): static
    {
        return $this->options(Navigator::TYPE_PATH);
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
        return (bool) ($this->options & static::MODE_ESCAPE);
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
        return $this->options(
            $bool
                ? $this->getOptions() | static::MODE_ESCAPE
                : $this->getOptions() & ~static::MODE_ESCAPE
        );
    }

    /**
     * Method to get property Mute
     *
     * @return  bool
     */
    public function isMute(): bool
    {
        return (bool) ($this->options & static::MODE_MUTE);
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
        return $this->options(
            $mute
                ? $this->getOptions() | static::MODE_MUTE
                : $this->getOptions() & ~static::MODE_MUTE
        );
    }

    protected function handleError(RouteNotFoundException $e): string
    {
        if (!($this->computedOptions() & static::MODE_MUTE)) {
            throw $e;
        }

        if ($this->computedOptions() & static::DEBUG_ALERT) {
            return "javascript: alert('{$e->getMessage()}');";
        }

        return '#';
    }

    public function computedOptions(): int
    {
        return $this->navigator->getOptions() | $this->getOptions();
    }

    /**
     * @return int
     */
    public function getOptions(): int
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
                foreach ($vars as $key => $var) {
                    $uri = $uri->withVar($key, $var);
                }
            }
        } else {
            $uri = new Uri(parent::__toString());
        }

        $uri = ltrim((string) $uri, '/');

        $options = $this->navigator->getOptions() | $this->options;

        if ($options & static::TYPE_RAW) {
            return $this->cache = $uri;
        }

        if ($options & static::TYPE_FULL) {
            return $this->cache = $this->navigator->absolute($uri, static::TYPE_FULL);
        }

        return $this->cache = $this->navigator->absolute($uri, static::TYPE_PATH);
    }

    public function withMessage(string|array $messages, string $type = 'info'): static
    {
        $this->navigator->getAppContext()->addMessage($messages, $type);

        return $this;
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
    }

    public function resetCache(): void
    {
        $this->cache = null;
    }
}
