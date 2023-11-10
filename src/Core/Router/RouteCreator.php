<?php

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

    public function controller(string $handler): static
    {
        $this->options['extra']['default_controller'] = $handler;

        return $this;
    }

    public function view(string $view): static
    {
        $this->options['extra']['default_view'] = $view;

        return $this;
    }
}
