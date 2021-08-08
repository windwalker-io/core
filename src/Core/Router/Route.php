<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

/**
 * The Route class.
 */
class Route implements \JsonSerializable
{
    use RouteConfigurationTrait;

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
     * groups
     *
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
     * getGroups
     *
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

    /**
     * compile
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function compile(): Route
    {
        $new = clone $this;

        $name   = $this->getName();
        $groups = $this->getGroups();

        // Set group data
        $keys = ['methods', 'actions', 'variables', 'requirements', 'scheme', 'port', 'sslPort', 'hooks'];

        foreach ($groups as $groupData) {
            foreach ($keys as $i => $key) {
                if (isset($groupData[$key])) {
                    $new->$key($groupData[$key]);

                    unset($groupData[$key]);
                }
            }

            if (isset($groupData['extra'])) {
                $new->extraValues($groupData['extra']);
            }
        }

        $options = $this->getOptions();

        if (!isset($options['pattern'])) {
            throw new \LogicException('Route: ' . $name . ' has no pattern.');
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
        $namespaces[] = $this->name;

        $new->name = implode('::', $namespaces);

        $options['extra']['action']      = $options['actions'] ?? [];
        $options['extra']['hook']        = $options['hooks'] ?? [];
        $options['extra']['middlewares'] = $options['middlewares'] ?? [];
        $options['extra']['groups'] = $groups;

        $new->setOptions($options);

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
        return '/' . trim(parse_url($pattern, PHP_URL_PATH), ' /');
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
        return get_object_vars($this);
    }
}
