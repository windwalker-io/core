<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

use LogicException;
use Windwalker\Data\Collection;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Classes\FlowControlTrait;
use Windwalker\Utilities\TypeCast;

use function Windwalker\glob_all;

/**
 * The RouteCreator class.
 */
class RouteCreator implements RouteCreatorInterface
{
    use RouteConfigurationTrait;
    use FlowControlTrait;
    use RouteCreatorTrait;

    /**
     * @var Route[]|Collection
     */
    protected ?Collection $routes = null;

    /**
     * Property groups.
     *
     * @var  array
     */
    protected array $groups = [];

    /**
     * Property preparedGroups.
     *
     * @var  ?Collection
     */
    protected ?Collection $preparedGroups = null;

    /**
     * Property group.
     *
     * @var  string
     */
    protected string $group;

    /**
     * RouteCreator constructor.
     *
     * @param  string  $group
     */
    public function __construct(string $group = 'root')
    {
        $this->group = $group;

        $this->routes = new Collection();
        $this->preparedGroups = new Collection();
    }

    /**
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function get(string $name, string|callable|null $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods('GET');
    }

    /**
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function post(string $name, string|callable|null $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods('POST');
    }

    /**
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function put(string $name, string|callable|null $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods('PUT');
    }

    /**
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function patch(string $name, string|callable|null $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods('PATCH');
    }

    /**
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function save(string $name, string|callable|null $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods(['PUT', 'PATCH', 'POST']);
    }

    /**
     * @param  string                $name
     * @param  string|callable|null  $pattern
     * @param  array                 $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function delete(string $name, string|callable|null $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods('DELETE');
    }
}
