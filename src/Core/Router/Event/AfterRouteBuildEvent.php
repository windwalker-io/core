<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Router\Event;

use Windwalker\Core\Router\Navigator;
use Windwalker\Event\AbstractEvent;

/**
 * The AfterRouteBuildEvent class.
 */
class AfterRouteBuildEvent extends AbstractEvent
{
    protected string $url = '';

    protected string $route = '';

    protected array $query = [];

    protected Navigator $navigator;

    protected int $options = 0;

    /**
     * @return string
     */
    public function &getRoute(): string
    {
        return $this->route;
    }

    /**
     * @param  string  $route
     *
     * @return  static  Return self to support chaining.
     */
    public function setRoute(string $route): static
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @return array
     */
    public function &getQuery(): array
    {
        return $this->query;
    }

    /**
     * @param  array  $query
     *
     * @return  static  Return self to support chaining.
     */
    public function setQuery(array $query): static
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return Navigator
     */
    public function getNavigator(): Navigator
    {
        return $this->navigator;
    }

    /**
     * @param  Navigator  $navigator
     *
     * @return  static  Return self to support chaining.
     */
    public function setNavigator(Navigator $navigator): static
    {
        $this->navigator = $navigator;

        return $this;
    }

    /**
     * @return int
     */
    public function getOptions(): int
    {
        return $this->options;
    }

    /**
     * @param  int  $options
     *
     * @return  static  Return self to support chaining.
     */
    public function setOptions(int $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return string
     */
    public function &getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param  string  $url
     *
     * @return  static  Return self to support chaining.
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }
}
