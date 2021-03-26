<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Pagination;

use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\Service\RendererService;
use Windwalker\Uri\Uri;

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
     * @var int
     */
    protected int $total = 0;

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
     * @var  callable
     */
    protected mixed $template;

    /**
     * Property route.
     *
     * @var RouteUri|null
     */
    protected RouteUri|null $route = null;

    /**
     * Pagination constructor.
     *
     * @param  Navigator        $navigator
     * @param  SystemUri        $systemUri
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
        return $this->total;
    }

    /**
     * Method to set property total
     *
     * @param  int  $total
     *
     * @return  static  Return self to support chaining.
     */
    public function total(int $total): static
    {
        $this->total = (int) $total;

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
     * @param  callable|null          $template
     *
     * @return string
     */
    public function render(?PaginationResult $result = null, callable $template = null): string
    {
        $result ??= $this->compile();
        $template ??= $this->getTemplate();

        return $template(
            [
                'result' => $result,
                'pagination' => $this
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
            throw new \InvalidArgumentException('Number of neighboring pages must be at least 1');
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
            throw new \InvalidArgumentException('Items per page must be at least 1');
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
        if ($this->limit === 0) {
            return 1;
        }

        return (int) ceil($this->total / $this->limit);
    }

    /**
     * Method to get property Template
     *
     * @return  callable
     */
    public function getTemplate(): callable
    {
        return $this->template;
    }

    /**
     * Method to set property template
     *
     * @param  callable  $template
     *
     * @return static Return self to support chaining.
     */
    public function template(callable $template): static
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
            return new RouteUri($this->systemUri->full, compact('page'), $this->navigator);
        }

        return $route->withVar('page', $page);
    }
}
