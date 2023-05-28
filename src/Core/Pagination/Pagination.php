<?php

/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

declare(strict_types=1);

namespace Windwalker\Core\Pagination;

use InvalidArgumentException;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\Router\SystemUri;

/**
 * The Pagination class.
 *
 * @since  2.0
 */
class Pagination
{
    /**
     * Number of the first page
     */
    public const BASE_PAGE = 1;

    /**
     * First page
     */
    public const FIRST = 'first';

    /**
     * The page before the previous neighbour pages
     */
    public const LESS = 'less';

    /**
     * Previous pages
     */
    public const PREVIOUS = 'previous';

    /**
     * Previous pages
     */
    public const LOWER = 'lower';

    /**
     * Current page
     */
    public const CURRENT = 'current';

    /**
     * Next pages
     */
    public const HIGHER = 'higher';

    /**
     * Next pages
     */
    public const NEXT = 'next';

    /**
     * The page after the next neighbour pages
     */
    public const MORE = 'more';

    /**
     * Last page
     */
    public const LAST = 'last';

    /**
     * Total Items
     *
     * @var int|callable|null
     */
    protected $total = null;

    /**
     * Number of the current page
     *
     * @var int
     */
    protected int $current = 1;

    /**
     * Items per page
     *
     * @var int
     */
    protected int $limit = 10;

    /**
     * Offset
     *
     * @var int
     */
    protected int $offset = 0;

    /**
     * Number of neighboring pages at the left and the right sides
     *
     * @var int
     */
    protected int $neighbours = 4;

    /**
     * Property template.
     *
     * @var  callable|string
     */
    protected mixed $template = null;

    /**
     * Property route.
     *
     * @var RouteUri|null
     */
    protected RouteUri|null $route = null;

    /**
     * Pagination constructor.
     *
     * @param  Navigator  $navigator
     * @param  SystemUri  $systemUri
     * @param  RendererService  $rendererService
     */
    public function __construct(
        protected Navigator $navigator,
        protected SystemUri $systemUri,
        protected RendererService $rendererService
    ) {
        //
    }

    /**
     * Display
     *
     * @return PaginationResult
     */
    public function compile(): PaginationResult
    {
        return new PaginationResult($this);
    }

    /**
     * Method to get property Total
     *
     * @return  int
     */
    public function getTotal(): int
    {
        if (!is_int($this->total)) {
            $this->total = ($this->total)();
        }

        return $this->total;
    }

    /**
     * Method to set property total
     *
     * @param  int|callable  $total
     *
     * @return  static  Return self to support chaining.
     */
    public function total(int|callable $total): static
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Method to get property Current
     *
     * @return  int
     */
    public function getCurrent(): int
    {
        return $this->current;
    }

    /**
     * Method to set property current
     *
     * @param  int  $current
     *
     * @return  static  Return self to support chaining.
     */
    public function currentPage(int $current): static
    {
        $this->current = (int) $current;

        if ($this->current < self::BASE_PAGE) {
            $this->current = self::BASE_PAGE;
        }

        return $this;
    }

    /**
     * Method to get property Offset
     *
     * @return  int
     */
    public function getOffset(): int
    {
        return abs(($this->current * $this->limit - $this->limit));
    }

    /**
     * render
     *
     * @param  PaginationResult|null  $result
     * @param  callable|string|null   $template
     * @param  array                  $options
     *
     * @return string
     */
    public function render(
        ?PaginationResult $result = null,
        callable|string $template = null,
        array $options = []
    ): string {
        $result ??= $this->compile();
        $template ??= $this->getTemplate();

        if (is_string($template)) {
            $template = $this->rendererService->make($template, $options);
        }

        if (!is_callable($template)) {
            throw new InvalidArgumentException('Template Should be string or callable.');
        }

        return $template(
            [
                'result' => $result,
                'pagination' => $this,
            ]
        );
    }

    /**
     * Method to set property neighbours
     *
     * @param  int  $neighbours
     *
     * @return  static  Return self to support chaining.
     */
    public function neighbours(int $neighbours): static
    {
        $this->neighbours = (int) $neighbours;

        if ($this->neighbours <= 0) {
            throw new InvalidArgumentException('Number of neighboring pages must be at least 1');
        }

        return $this;
    }

    /**
     * Method to get property Neighbours
     *
     * @return  int
     */
    public function getNeighbours(): int
    {
        return $this->neighbours;
    }

    /**
     * Method to get property Limit
     *
     * @return  int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Method to set property limit
     *
     * @param  int  $limit
     *
     * @return  static  Return self to support chaining.
     */
    public function limit(int $limit): static
    {
        $this->limit = (int) $limit;

        if ($this->limit < 0) {
            throw new InvalidArgumentException('Items per page must be at least 1');
        }

        return $this;
    }

    /**
     * Method to get property Pages
     *
     * @return  int
     */
    public function getPages(): int
    {
        if ($this->getLimit() === 0) {
            return 1;
        }

        return (int) ceil($this->getTotal() / $this->getLimit());
    }

    /**
     * Method to get property Template
     *
     * @return  callable|string|null
     */
    public function getTemplate(): callable|string|null
    {
        return $this->template;
    }

    /**
     * Method to set property template
     *
     * @param  callable|string|null  $template
     *
     * @return static Return self to support chaining.
     */
    public function template(callable|string|null $template): static
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return RouteUri|null
     */
    public function getRoute(): ?RouteUri
    {
        return $this->route;
    }

    /**
     * @param  RouteUri|null  $route
     *
     * @return  static  Return self to support chaining.
     */
    public function route(?RouteUri $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function toRoute(int $page): RouteUri
    {
        $route = $this->route;

        if ($route === null) {
            return $this->navigator->self()->page($page);
        }

        return $route->withVar('page', $page);
    }
}
