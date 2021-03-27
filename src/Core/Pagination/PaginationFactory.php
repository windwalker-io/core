<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

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
     * PaginationFactory constructor.
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
    }

    public function create(int $current = 1, int $limit = 10, int $neighbours = 4): Pagination
    {
        return (new Pagination($this->navigator, $this->systemUri, $this->rendererService))
            ->currentPage($current)
            ->limit($limit)
            ->neighbours($neighbours);
    }
}
