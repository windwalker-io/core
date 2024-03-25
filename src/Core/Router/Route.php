<?php

declare(strict_types=1);

namespace Windwalker\Core\Router;

use Closure;
use JsonSerializable;
use LogicException;
use Psr\Http\Message\UriInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\DI\Definition\ObjectBuilderDefinition;
use Windwalker\Uri\Uri;
use Windwalker\Uri\UriNormalizer;
use Windwalker\Utilities\Classes\FlowControlTrait;

/**
 * @method $this getHandler(callable|array|string|null $handler, ?string $task = null)
 * @method $this postHandler(callable|array|string|null $handler, ?string $task = null)
 * @method $this putHandler(callable|array|string|null $handler, ?string $task = null)
 * @method $this patchHandler(callable|array|string|null $handler, ?string $task = null)
 * @method $this deleteHandler(callable|array|string|null $handler, ?string $task = null)
 * @method $this headHandler(callable|array|string|null $handler, ?string $task = null)
 * @method $this optionsHandler(callable|array|string|null $handler, ?string $task = null)
 */
class Route implements JsonSerializable
{
    use RouteConfigurationTrait;
    use FlowControlTrait;

    protected array $groups = [];

    /**
     * Route constructor.
     *
     * @param  string|null  $name
     * @param  string       $pattern
     * @param  array        $options
     */
    public function __construct(
        protected ?string $name,
        string $pattern = '',
        array $options = []
    ) {
        $this->pattern($pattern);

        $this->prepareOptions($options);
    }

    /**
     * pattern
     *
     * @param  string  $value
     *
     * @return  static
     *
     * @since  3.5
     */
    public function pattern(string $value): self
    {
        $this->options['pattern'] = $value;

        return $this;
    }

    public function getPattern(): string
    {
        return $this->options['pattern'] ?? '';
    }

    public function handler(string|array|callable $handler, ?string $task = null): static
    {
        if ($task !== null) {
            $handler = [$handler, $task];
        }

        return $this->allHandlers($handler);
    }

    public function controller(string|array|callable $handler, ?string $task = null): static
    {
        return $this->handler($handler, $task);
    }

    public function redirect(mixed $to, array $query = [], int $options = NavConstantInterface::TYPE_PATH): static
    {
        return $this->controller(
            function (Navigator $nav, ApplicationInterface $app) use ($to, $query, $options) {
                if ($to instanceof Closure) {
                    return $app->call($to);
                }

                if ($to instanceof UriInterface) {
                    return $to;
                }

                if (is_string($to) && SystemUri::isAbsoluteUrl($to)) {
                    return new Uri($to);
                }

                return $nav->to($to, $query, $options);
            }
        );
    }

    public function alias(string|array|null $route): static
    {
        if ($route === null) {
            $route = [];
        } else {
            $route = array_unique(array_merge((array) $route, $this->options['aliases'] ?? []));
        }

        $this->options['aliases'] = $route;

        return $this;
    }

    public function view(string $view): static
    {
        return $this->var('view', $view);
    }

    /**
     * group
     *
     * @param  string  $group
     *
     * @return  static
     *
     * @since  3.5
     */
    public function group(string $group): static
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * @param  array  $groups
     *
     * @return  static
     *
     * @since  3.5
     */
    public function groups(array $groups): static
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * @return  array
     *
     * @since  3.5
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Method to get property Name
     *
     * @return  string
     *
     * @since  3.5
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function isGroup(string ...$groups): bool
    {
        foreach ($groups as $group) {
            if (array_key_exists($group, $this->groups)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return  Route
     *
     * @since  3.5
     */
    public function compile(): Route
    {
        $new = clone $this;

        $name = $this->getName();
        $groups = $this->getGroups();

        // Set group data
        $keys = [
            'methods',
            'actions',
            'vars',
            'requirements',
            'scheme',
            'port',
            'sslPort',
            'hooks',
            'hosts',
        ];

        foreach ($groups as $groupData) {
            foreach ($keys as $i => $key) {
                if (isset($groupData[$key])) {
                    $new->$key($groupData[$key]);

                    unset($groupData[$key]);
                }
            }

            $new->layoutPaths(
                ...($groupData['layoutPaths'] ?? [])
            );

            if (isset($groupData['extra'])) {
                $new->extraValues($groupData['extra']);
            }
        }

        $options = $new->getOptions();

        if (!isset($options['pattern'])) {
            throw new LogicException('Route: ' . $name . ' has no pattern.');
        }

        // Prefix
        $prefixes = array_filter(
            array_column($groups, 'prefix'),
            static fn($v): int => strlen(trim($v, '/'))
        );

        $options['pattern'] = static::sanitize(implode('/', $prefixes) . $options['pattern']);

        // Namespace
        $namespaces = array_filter(
            array_column($groups, 'namespace')
        );
        $options['extra']['namespace'] = implode('::', $namespaces);
        $nss = $namespaces;
        $nss[] = $this->name;

        $new->name = implode('::', $nss);

        $options['aliases'] ??= [];

        foreach ($options['aliases'] as &$alias) {
            $nss = $namespaces;
            $nss[] = $alias;
            $alias = implode('::', $nss);
        }

        unset($alias);

        $options['extra']['action'] = $options['actions'] ?? [];
        $options['extra']['hook'] = $options['hooks'] ?? [];
        $options['extra']['middlewares'] = $options['middlewares'] ?? [];
        $options['extra']['subscribers'] = $options['subscribers'] ?? [];
        $options['extra']['groups'] = $groups;

        $new->setOptions($options);

        if ($options['extra']['default_controller'] ?? null) {
            foreach ($new->options['handlers'] as $k => &$handler) {
                if (is_string($handler)) {
                    $handler = [
                        $options['extra']['default_controller'],
                        $handler
                    ];
                }
            }
            
            unset($handler);
        }

        if ($options['extra']['default_view'] ?? null) {
            $new->view($options['extra']['default_view']);
        }

        return $new;
    }

    /**
     * Sanitize and explode the pattern.
     *
     * @param  string  $pattern
     *
     * @return  string
     */
    public static function sanitize(string $pattern): string
    {
        return '/' . trim(parse_url(UriNormalizer::cleanPath($pattern), PHP_URL_PATH), ' /');
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize(): array
    {
        $properties = get_object_vars($this);

        foreach ($properties['groups'] ?? [] as $gn => $group) {
            foreach ($group['middlewares'] ?? [] as $key => $middleware) {
                if ($middleware instanceof ObjectBuilderDefinition) {
                    $properties['groups'][$gn]['middlewares'][$key] = $middleware->getClass();
                } elseif (is_object($middleware)) {
                    $properties['groups'][$gn]['middlewares'][$key] = $middleware::class;
                }
            }
        }

        foreach ($properties['options']['middlewares'] ?? [] as &$middleware) {
            if ($middleware instanceof ObjectBuilderDefinition) {
                $middleware = $middleware->getClass();
            } elseif (is_object($middleware)) {
                $middleware = $middleware::class;
            }
        }

        return $properties;
    }
}
