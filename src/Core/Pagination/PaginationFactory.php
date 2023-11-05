<?php

declare(strict_types=1);

namespace Windwalker\Core\Pagination;

use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;

/**
 * The PaginationFactory class.
 */
class PaginationFactory
{
    /**
     * @var callable|string
     */
    protected mixed $defaultTemplate = null;

    protected int $defaultNeighbours = 4;

    /**
     * PaginationFactory constructor.
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
    }

    public function create(int $current = 1, int $limit = 10, ?int $neighbours = null): Pagination
    {
        return (new Pagination($this->navigator, $this->systemUri, $this->rendererService))
            ->currentPage($current)
            ->limit($limit)
            ->neighbours($neighbours ?? $this->getDefaultNeighbours())
            ->template($this->defaultTemplate);
    }

    /**
     * @return callable|string|null
     */
    public function getDefaultTemplate(): callable|string|null
    {
        return $this->defaultTemplate;
    }

    /**
     * @param  callable|string|null  $defaultTemplate
     *
     * @return  static  Return self to support chaining.
     */
    public function setDefaultTemplate(callable|string|null $defaultTemplate): static
    {
        $this->defaultTemplate = $defaultTemplate;

        return $this;
    }

    /**
     * @return int
     */
    public function getDefaultNeighbours(): int
    {
        return $this->defaultNeighbours;
    }

    /**
     * @param  int  $defaultNeighbours
     *
     * @return  static  Return self to support chaining.
     */
    public function setDefaultNeighbours(int $defaultNeighbours): static
    {
        $this->defaultNeighbours = $defaultNeighbours;

        return $this;
    }
}
