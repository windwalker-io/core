<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Pagination;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Pagination\Pagination;
use Windwalker\Core\Pagination\PaginationResult;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\Router\SystemUri;

class PaginationResultTest extends TestCase
{
    protected Navigator $nav;

    protected SystemUri $systemUri;

    protected RendererService $rendererService;

    #[Test]
    public function compile_withZeroTotal_leavesEveryGetterEmpty(): void
    {
        $result = new PaginationResult($this->makePagination(total: 0, limit: 10, current: 1, neighbours: 4));

        self::assertNull($result->getFirst());
        self::assertNull($result->getLess());
        self::assertNull($result->getPrevious());
        self::assertSame([], $result->getLowers());
        self::assertNull($result->getCurrent());
        self::assertSame([], $result->getHighers());
        self::assertNull($result->getNext());
        self::assertNull($result->getMore());
        self::assertNull($result->getLast());
        self::assertSame([], $result->getAll());
    }

    #[Test]
    public function compile_withZeroLimit_leavesEveryGetterEmpty(): void
    {
        $result = new PaginationResult($this->makePagination(total: 100, limit: 0, current: 1, neighbours: 4));

        self::assertNull($result->getCurrent());
        self::assertSame([], $result->getAll());
    }

    #[Test]
    public function compile_withOnlyOnePage_leavesEveryGetterEmpty(): void
    {
        $result = new PaginationResult($this->makePagination(total: 5, limit: 10, current: 1, neighbours: 4));

        self::assertNull($result->getCurrent());
        self::assertSame([], $result->getAll());
    }

    #[Test]
    public function compile_onFirstPage_hasNoPreviousOrFirstButHasNextAndHighers(): void
    {
        $result = new PaginationResult($this->makePagination(total: 50, limit: 10, current: 1, neighbours: 4));

        self::assertNull($result->getFirst());
        self::assertNull($result->getLess());
        self::assertNull($result->getPrevious());
        self::assertSame([], $result->getLowers());
        self::assertSame(1, $result->getCurrent()?->page);
        self::assertSame([2, 3, 4, 5], array_map(static fn($item) => $item->page, $result->getHighers()));
        self::assertSame(2, $result->getNext()?->page);
        self::assertNull($result->getMore());
        self::assertNull($result->getLast());
    }

    #[Test]
    public function compile_onLastPage_hasFirstAndPreviousButNoNextOrLast(): void
    {
        $result = new PaginationResult($this->makePagination(total: 50, limit: 10, current: 5, neighbours: 4));

        self::assertSame(1, $result->getFirst()?->page);
        self::assertNull($result->getLess());
        self::assertSame(4, $result->getPrevious()?->page);
        self::assertSame([4, 3, 2, 1], array_map(static fn($item) => $item->page, $result->getLowers()));
        self::assertSame(5, $result->getCurrent()?->page);
        self::assertSame([], $result->getHighers());
        self::assertNull($result->getNext());
        self::assertNull($result->getMore());
        self::assertNull($result->getLast());
    }

    #[Test]
    public function compile_onMiddlePage_populatesAllNeighbourSections(): void
    {
        $result = new PaginationResult($this->makePagination(total: 200, limit: 10, current: 10, neighbours: 2));

        self::assertSame(1, $result->getFirst()?->page);
        self::assertSame(7, $result->getLess()?->page);
        self::assertSame([9, 8], array_map(static fn($item) => $item->page, $result->getLowers()));
        self::assertSame(9, $result->getPrevious()?->page);
        self::assertSame(10, $result->getCurrent()?->page);
        self::assertSame([11, 12], array_map(static fn($item) => $item->page, $result->getHighers()));
        self::assertSame(11, $result->getNext()?->page);
        self::assertSame(13, $result->getMore()?->page);
        self::assertSame(20, $result->getLast()?->page);
    }

    #[Test]
    public function compile_inSimpleMode_onlyUsesPreviousNextAndOptionalFirst(): void
    {
        $result = new PaginationResult(
            $this->makePagination(total: 0, limit: 10, current: 3, neighbours: 4, simple: true)
        );

        self::assertSame(1, $result->getFirst()?->page);
        self::assertNull($result->getLess());
        self::assertSame([], $result->getLowers());
        self::assertSame(2, $result->getPrevious()?->page);
        self::assertSame(3, $result->getCurrent()?->page);
        self::assertSame([], $result->getHighers());
        self::assertSame(4, $result->getNext()?->page);
        self::assertNull($result->getMore());
        self::assertNull($result->getLast());
    }

    #[Test]
    public function compile_inSimpleModeOnFirstPage_hasNoPreviousOrFirst(): void
    {
        $result = new PaginationResult(
            $this->makePagination(total: 0, limit: 10, current: 1, neighbours: 4, simple: true)
        );

        self::assertNull($result->getFirst());
        self::assertNull($result->getPrevious());
        self::assertSame(1, $result->getCurrent()?->page);
        self::assertSame(2, $result->getNext()?->page);
    }

    #[Test]
    public function getPages_ordersLowersCurrentAndHighersByPageNumber(): void
    {
        $result = new PaginationResult($this->makePagination(total: 200, limit: 10, current: 10, neighbours: 2));

        self::assertSame(
            [8, 9, 10, 11, 12],
            array_keys($result->getPages())
        );
    }

    #[Test]
    public function getAll_includesFirstLessMoreLastInPageOrder(): void
    {
        $result = new PaginationResult($this->makePagination(total: 200, limit: 10, current: 10, neighbours: 2));

        self::assertSame(
            [1, 7, 8, 9, 10, 11, 12, 13, 20],
            array_map(static fn($item) => $item->page, $result->getAll())
        );
    }

    #[Test]
    public function navs_returnsOnlyFirstPreviousNextLastKeysInOrder(): void
    {
        $result = new PaginationResult($this->makePagination(total: 200, limit: 10, current: 10, neighbours: 2));

        $navs = $result->navs();

        self::assertSame(['first', 'previous', 'next', 'last'], array_keys($navs));
        self::assertSame(1, $navs['first']->page);
        self::assertSame(9, $navs['previous']->page);
        self::assertSame(11, $navs['next']->page);
        self::assertSame(20, $navs['last']->page);
    }

    #[Test]
    public function navLinks_mapsEachNavEntryToItsLink(): void
    {
        $result = new PaginationResult($this->makePagination(total: 200, limit: 10, current: 10, neighbours: 2));

        self::assertSame(
            ['first' => 'LINK', 'previous' => 'LINK', 'next' => 'LINK', 'last' => 'LINK'],
            $result->navLinks()
        );
    }

    #[Test]
    public function jsonSerialize_returnsGetAll(): void
    {
        $result = new PaginationResult($this->makePagination(total: 200, limit: 10, current: 10, neighbours: 2));

        self::assertSame($result->getAll(), $result->jsonSerialize());
    }

    #[Test]
    public function getIterator_wrapsGetAllInArrayIterator(): void
    {
        $result = new PaginationResult($this->makePagination(total: 200, limit: 10, current: 10, neighbours: 2));

        self::assertSame($result->getAll(), iterator_to_array($result->getIterator()));
    }

    #[Test]
    public function getItem_returnsNullWhenPageIsNotPositive(): void
    {
        $result = new PaginationResult($this->makePagination(total: 200, limit: 10, current: 10, neighbours: 2));

        $method = new \ReflectionMethod($result, 'getItem');

        self::assertNull($method->invoke($result, 'first', 0));
        self::assertNull($method->invoke($result, 'first', -1));
    }

    #[Before]
    protected function prepareDependencies(): void
    {
        $route = $this->createStub(RouteUri::class);
        $route->method('page')->willReturnSelf();
        $route->method('withVar')->willReturnSelf();
        $route->method('full')->willReturnSelf();
        $route->method('__toString')->willReturn('LINK');

        $this->nav = $this->createStub(Navigator::class);
        $this->nav->method('self')->willReturn($route);

        $this->systemUri = $this->createStub(SystemUri::class);
        $this->rendererService = $this->createStub(RendererService::class);
    }

    protected function makePagination(
        int|\Closure $total,
        int $limit,
        int $current,
        int $neighbours,
        bool $simple = false
    ): Pagination {
        $pagination = new Pagination($this->nav, $this->systemUri, $this->rendererService);

        return $pagination->total($total)
            ->limit($limit)
            ->currentPage($current)
            ->neighbours($neighbours)
            ->simple($simple);
    }
}
