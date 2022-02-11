<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Pagination;

use Windwalker\Core\Ioc;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Router\MainRouter;
use Windwalker\Core\Router\PackageRouter;
use Windwalker\Core\Router\RouteBuilderInterface;
use Windwalker\Core\Widget\WidgetHelper;
use Windwalker\Uri\Uri;

/**
 * The Pagination class.
 *
 * @note   Based on https://github.com/Kilte/pagination
 *
 * @since  2.0
 */
class Pagination
{
    /**
     * Number of the first page
     */
    const BASE_PAGE = 1;

    /**
     * First page
     */
    const FIRST = 'first';

    /**
     * The page before the previous neighbour pages
     */
    const LESS = 'less';

    /**
     * Previous pages
     */
    const PREVIOUS = 'previous';

    /**
     * Previous pages
     */
    const LOWER = 'lower';

    /**
     * Current page
     */
    const CURRENT = 'current';

    /**
     * Next pages
     */
    const HIGHER = 'higher';

    /**
     * Next pages
     */
    const NEXT = 'next';

    /**
     * The page after the next neighbour pages
     */
    const MORE = 'more';

    /**
     * Last page
     */
    const LAST = 'last';

    /**
     * Total Items
     *
     * @var int
     */
    protected $total;

    /**
     * Number of the current page
     *
     * @var int
     */
    protected $current;

    /**
     * Items per page
     *
     * @var int
     */
    protected $limit;

    /**
     * Total pages
     *
     * @var int
     */
    protected $pages;

    /**
     * Offset
     *
     * @var int
     */
    protected $offset;

    /**
     * Number of neighboring pages at the left and the right sides
     *
     * @var int
     */
    protected $neighbours;

    /**
     * Property result.
     *
     * @var  PaginationResult
     */
    protected $result;

    /**
     * Property route.
     *
     * @var  PackageRouter
     */
    protected $router;

    /**
     * Property template.
     *
     * @var  array
     */
    protected $template = [
        'layout' => 'windwalker.pagination.default',
        'engine' => 'php',
    ];

    /**
     * Property route.
     *
     * @var  array
     */
    protected $route = [
        'route' => null,
        'query' => [],
        'config' => [],
    ];

    /**
     * simple
     *
     * @param int $current
     * @param int $limit
     *
     * @return  static
     */
    public static function simple($current, $limit = 10)
    {
        return (new Pagination($current, $limit, -1, 1))->template('windwalker.pagination.simple');
    }

    /**
     * Create instance
     *
     * @param int $current    Number of the current page
     * @param int $limit      Items per page
     * @param int $total      Total items
     * @param int $neighbours Number of neighboring pages at the left and the right sides
     */
    public function __construct($current, $limit = 10, $total = 0, $neighbours = 4)
    {
        $this->total      = (int) $total;
        $this->current    = (int) $current;
        $this->limit      = (int) $limit;
        $this->neighbours = (int) $neighbours;

        if ($this->limit < 0) {
            throw new \InvalidArgumentException('Items per page must be at least 1');
        }

        if ($this->neighbours <= 0) {
            throw new \InvalidArgumentException('Number of neighboring pages must be at least 1');
        }

        if ($this->current < self::BASE_PAGE) {
            $this->current = self::BASE_PAGE;
        }

        if ($this->limit == 0) {
            $this->pages = 1;
        } else {
            $this->pages = (int) ceil($this->total / $this->limit);
        }

        if ($this->current > $this->pages && $this->pages != 0) {
            $this->current = $this->pages;
        }

        $this->offset = abs(intval($this->current * $this->limit - $this->limit));
    }

    /**
     * Display
     *
     * @return static
     */
    public function build()
    {
        $this->result = new PaginationResult();

        if ($this->total === 0 || $this->limit === 0 || $this->pages === 1) {
            return $this;
        }

        // Lower
        $offset = $this->current - 1;

        // -1 is simple pagination
        if ($this->total === -1) {
            $neighbours = 1;
        } else {
            $neighbours = $this->current - $this->neighbours;
            $neighbours = $neighbours < static::BASE_PAGE ? static::BASE_PAGE : $neighbours;
        }

        for ($i = $offset; $i >= $neighbours; $i--) {
            $this->result->addLower($i);
        }

        if ($neighbours - static::BASE_PAGE >= 2) {
            $this->result->setLess($neighbours - 1);
        }

        $this->result->setPrevious($this->current - 1);

        // First
        if ($this->current - $this->neighbours > static::BASE_PAGE) {
            $this->result->setFirst(static::BASE_PAGE);
        }

        // Higher
        $offset     = $this->current + 1;
        $neighbours = $this->current + $this->neighbours;
        $neighbours = $neighbours > $this->pages ? $this->pages : $neighbours;

        for ($i = $offset; $i <= $neighbours; $i++) {
            $this->result->addHigher($i);
        }

        if ($this->pages - $neighbours > 0) {
            $this->result->setMore($neighbours + 1);
        }

        // Show next button if not last page or for simple pagination
        if ($this->total === -1 || $this->current !== $this->pages) {
            $this->result->setNext($this->current + 1);
        }

        // Last
        if ($this->current + $this->neighbours < $this->pages) {
            $this->result->setLast($this->pages);
        }

        // Current
        $this->result->setCurrent($this->current);

        return $this;
    }

    /**
     * Method to get property Total
     *
     * @return  int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Method to set property total
     *
     * @param   int $total
     *
     * @return  static  Return self to support chaining.
     */
    public function total($total)
    {
        $this->total = (int) $total;

        return $this;
    }

    /**
     * Method to get property Current
     *
     * @return  int
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * Method to set property current
     *
     * @param   int $current
     *
     * @return  static  Return self to support chaining.
     */
    public function currentPage($current)
    {
        $this->current = (int) $current;

        return $this;
    }

    /**
     * Method to get property Offset
     *
     * @return  int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Method to set property offset
     *
     * @param   int $offset
     *
     * @return  static  Return self to support chaining.
     */
    #[\ReturnTypeWillChange]
    public function offset($offset)
    {
        $this->offset = (int) $offset;

        return $this;
    }

    /**
     * Method to get property Result
     *
     * @return  PaginationResult
     */
    public function getResult()
    {
        $this->build();

        return $this->result;
    }

    /**
     * Method to set property result
     *
     * @param   PaginationResult $result
     *
     * @return  static  Return self to support chaining.
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * render
     *
     * @return string
     * @throws \ReflectionException
     */
    public function render()
    {
        [$layout, $engine] = array_values($this->getTemplate());

        $widget = WidgetHelper::createWidget($layout, $engine);

        [$route, $query, $config] = array_values($this->route);

        $result = $this->getResult();

        if (!$result->getPages()) {
            return null;
        }

        // If not route provided, use current route.
        if ($route === null) {
            $route = function ($queries) use ($query, $config) {
                $queries = array_merge((array) $queries, (array) $query);

                $uri = new Uri(Ioc::getUriData()->full);

                foreach ($queries as $k => $v) {
                    $uri->setVar($k, $v);
                }

                if (!$this->getRouter()) {
                    return $uri->toString(['path', 'query', 'fragment']);
                }

                $route = Ioc::getConfig()->get('route.matched');

                return $this->getRouter()->route($route, $uri->getQuery(true), $config);
            };
        }

        // If route is string, wrap it as a callback
        if (!$route instanceof \Closure) {
            $route = function ($queries) use ($route, $query, $config) {
                $queries = array_merge((array) $queries, (array) $query);

                if (!$this->getRouter()) {
                    $package = PackageHelper::getPackage();

                    // CoreRoute object not exists, we use global Route object.
                    return $package->router->route($route, $queries, $config);
                }

                // Use custom Route object.
                return $this->getRouter()->route($route, $queries, $config);
            };
        }

        return $widget->render(['pagination' => $result, 'route' => $route]);
    }

    /**
     * Method to get property Route
     *
     * @return  RouteBuilderInterface
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Method to set property route
     *
     * @param   RouteBuilderInterface $router
     *
     * @return  static  Return self to support chaining.
     */
    public function setRouter(RouteBuilderInterface $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * route
     *
     * @param string $route
     * @param array  $query
     * @param array  $config
     *
     * @return  static
     */
    public function route($route, $query = [], $config = [])
    {
        $this->route = [
            'route' => $route,
            'query' => (array) $query,
            'config' => $config,
        ];

        return $this;
    }

    /**
     * Method to set property neighbours
     *
     * @param   int $neighbours
     *
     * @return  static  Return self to support chaining.
     */
    public function neighbours($neighbours)
    {
        $this->neighbours = (int) $neighbours;

        return $this;
    }

    /**
     * Method to get property Neighbours
     *
     * @return  int
     */
    public function getNeighbours()
    {
        return $this->neighbours;
    }

    /**
     * Method to get property Limit
     *
     * @return  int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Method to set property limit
     *
     * @param   int $limit
     *
     * @return  static  Return self to support chaining.
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;

        return $this;
    }

    /**
     * Method to get property Pages
     *
     * @return  int
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Method to set property pages
     *
     * @param   int $pages
     *
     * @return  static  Return self to support chaining.
     */
    public function pages($pages)
    {
        $this->pages = (int) $pages;

        return $this;
    }

    /**
     * Method to get property Template
     *
     * @return  array
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Method to set property template
     *
     * @param string $layout
     * @param string $engine
     *
     * @return static Return self to support chaining.
     */
    public function template($layout, $engine = 'php')
    {
        $this->template = [
            'layout' => $layout,
            'engine' => $engine,
        ];

        return $this;
    }
}
