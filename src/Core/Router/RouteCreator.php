<?php

declare(strict_types=1);

namespace Windwalker\Core\Router;

use Windwalker\Utilities\Classes\ChainingTrait;

class RouteCreator implements RouteCreatorInterface
{
    use RouteConfigurationTrait;
    use ChainingTrait;
    use RouteCreatorTrait;

    /**
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     *
     * @codeCoverageIgnore
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
     *
     * @codeCoverageIgnore
     */
    public function query(string $name, string|callable|null $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods('QUERY');
    }

    /**
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     *
     * @codeCoverageIgnore
     */
    public function fetch(string $name, string|callable|null $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods(['GET', 'QUERY']);
    }

    /**
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     *
     * @codeCoverageIgnore
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
     *
     * @codeCoverageIgnore
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
     *
     * @codeCoverageIgnore
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
     *
     * @codeCoverageIgnore
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
     *
     * @codeCoverageIgnore
     */
    public function delete(string $name, string|callable|null $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods('DELETE');
    }

    /**
     * @codeCoverageIgnore
     */
    public function controller(string $handler): static
    {
        $this->options['extra']['default_controller'] = $handler;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function view(string $view): static
    {
        $this->options['extra']['default_view'] = $view;

        return $this;
    }
}
