<?php

declare(strict_types=1);

namespace Windwalker\Core\Pagination;

use Closure;
use InvalidArgumentException;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\NavOptions;
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
    public const int BASE_PAGE = 1;

    /**
     * First page
     */
    public const string FIRST = 'first';

    /**
     * The page before the previous neighbour pages
     */
    public const string LESS = 'less';

    /**
     * Previous pages
     */
    public const string PREVIOUS = 'previous';

    /**
     * Previous pages
     */
    public const string LOWER = 'lower';

    /**
     * Current page
     */
    public const string CURRENT = 'current';

    /**
     * Next pages
     */
    public const string HIGHER = 'higher';

    /**
     * Next pages
     */
    public const string NEXT = 'next';

    /**
     * The page after the next neighbour pages
     */
    public const string MORE = 'more';

    /**
     * Last page
     */
    public const string LAST = 'last';

    /**
     * Total Items
     *
     * @var int|null|Closure
     */
    protected int|null|Closure $total = null;

    /**
     * Number of the current page
     *
     * @var int
     */
    public protected(set) int $current = 1;

    /**
     * Items per page
     *
     * @var int
     */
    public protected(set) int $limit = 10;

    /**
     * Offset
     *
     * @var int
     */
    public protected(set) int $offset = 0;

    /**
     * Number of neighboring pages at the left and the right sides
     *
     * @var int
     */
    public protected(set) int $neighbours = 4;

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
    public protected(set) RouteUri|null $route = null;

    public protected(set) bool $simple = false;

    public protected(set) array|bool|null $allowQuery = null;

    /**
     * Pagination constructor.
     *
     * @param  Navigator        $nav
     * @param  SystemUri        $systemUri
     * @param  RendererService  $rendererService
     */
    public function __construct(
        public protected(set) Navigator $nav,
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
        if ($this->isSimple()) {
            return -1;
        }

        if (!is_int($this->total)) {
            $this->total = ($this->total)($this);
        }

        return $this->total;
    }

    /**
     * Method to set property total
     *
     * @param  int|Closure  $total
     *
     * @return  static  Return self to support chaining.
     */
    public function total(int|Closure $total): static
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
        $this->current = $current;

        $this->positiveCurrentPage();

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
        callable|string|null $template = null,
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
            return $this->nav->self()->page($page);
        }

        return $route->withVar('page', $page);
    }

    public function isSimple(): bool
    {
        return $this->simple;
    }

    /**
     * @param  bool  $simple
     *
     * @return  static  Return self to support chaining.
     */
    public function simple(bool $simple = true): static
    {
        $this->simple = $simple;

        return $this;
    }

    /**
     * @return  void
     */
    protected function positiveCurrentPage(): void
    {
        if (is_int($this->current) && $this->current < self::BASE_PAGE) {
            $this->current = self::BASE_PAGE;
        }
    }

    public function configureNavigator(\Closure $callback): static
    {
        $this->nav = $callback($this->nav) ?? $this->nav;

        return $this;
    }

    public function allowQuery(array|bool|null $allowQuery = null, bool $replace = false): static
    {
        $this->nav = $this->nav->allowQuery($allowQuery, $replace);

        return $this;
    }
}
