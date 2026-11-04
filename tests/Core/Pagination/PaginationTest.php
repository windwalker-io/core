<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Pagination;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Pagination\Pagination;
use Windwalker\Core\Pagination\PaginationResult;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\Router\SystemUri;

class PaginationTest extends TestCase
{
    protected Navigator $nav;

    protected SystemUri $systemUri;

    protected RendererService $rendererService;

    protected Pagination $instance;

    #[Test]
    public function getTotal_returnsNegativeOneWhenSimple(): void
    {
        $this->instance->simple(true);
        $this->instance->total(50);

        self::assertSame(-1, $this->instance->getTotal());
    }

    #[Test]
    public function getTotal_returnsIntTotalDirectly(): void
    {
        $this->instance->total(123);

        self::assertSame(123, $this->instance->getTotal());
    }

    #[Test]
    public function getTotal_resolvesClosureOnceAndCachesResult(): void
    {
        $calls = 0;
        $this->instance->total(
            function () use (&$calls) {
                $calls++;

                return 456;
            }
        );

        self::assertSame(456, $this->instance->getTotal());
        self::assertSame(456, $this->instance->getTotal());
        self::assertSame(1, $calls);
    }

    #[Test]
    public function currentPage_clampsNonPositiveValuesToBasePage(): void
    {
        $this->instance->currentPage(0);
        self::assertSame(1, $this->instance->current);

        $this->instance->currentPage(-5);
        self::assertSame(1, $this->instance->current);

        $this->instance->currentPage(3);
        self::assertSame(3, $this->instance->current);
    }

    #[Test]
    public function getOffset_computesOffsetFromCurrentAndLimit(): void
    {
        $this->instance->currentPage(1)->limit(10);
        self::assertSame(0, $this->instance->getOffset());

        $this->instance->currentPage(3)->limit(10);
        self::assertSame(20, $this->instance->getOffset());
    }

    #[Test]
    public function neighbours_throwsWhenNotPositive(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Number of neighboring pages must be at least 1');

        $this->instance->neighbours(0);
    }

    #[Test]
    public function neighbours_acceptsPositiveValue(): void
    {
        $this->instance->neighbours(6);

        self::assertSame(6, $this->instance->neighbours);
    }

    #[Test]
    public function limit_throwsWhenNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Items per page must be at least 1');

        $this->instance->limit(-1);
    }

    #[Test]
    public function limit_acceptsZeroAndPositiveValues(): void
    {
        $this->instance->limit(0);
        self::assertSame(0, $this->instance->getLimit());

        $this->instance->limit(25);
        self::assertSame(25, $this->instance->getLimit());
    }

    #[Test]
    public function getPages_returnsOneWhenLimitIsZero(): void
    {
        $this->instance->limit(0)->total(999);

        self::assertSame(1, $this->instance->getPages());
    }

    #[Test]
    public function getPages_computesCeilOfTotalOverLimit(): void
    {
        $this->instance->limit(10)->total(25);

        self::assertSame(3, $this->instance->getPages());
    }

    #[Test]
    public function render_usesProvidedCallableTemplate(): void
    {
        $result = $this->createStub(PaginationResult::class);

        $output = $this->instance->render(
            $result,
            fn(array $data) => 'rendered:' . ($data['result'] === $result ? 'ok' : 'fail')
        );

        self::assertSame('rendered:ok', $output);
    }

    #[Test]
    public function render_resolvesStringTemplateThroughRendererService(): void
    {
        $result = $this->createStub(PaginationResult::class);
        $rendererService = $this->createMock(RendererService::class);
        $rendererService->expects($this->once())
            ->method('make')
            ->with('pagination::default', ['foo' => 'bar'])
            ->willReturn(fn(array $data) => 'string-template-output');

        $instance = new Pagination($this->nav, $this->systemUri, $rendererService);
        $instance->template('pagination::default');

        $output = $instance->render($result, null, ['foo' => 'bar']);

        self::assertSame('string-template-output', $output);
    }

    #[Test]
    public function render_throwsWhenTemplateIsNotCallableOrString(): void
    {
        $result = $this->createStub(PaginationResult::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Template Should be string or callable.');

        $this->instance->render($result);
    }

    #[Test]
    public function render_compilesResultWhenNotProvided(): void
    {
        $this->instance->total(0);

        $output = $this->instance->render(template: fn(array $data) => get_class($data['result']));

        self::assertSame(PaginationResult::class, $output);
    }

    #[Test]
    public function toRoute_usesNavigatorSelfWhenNoRouteSet(): void
    {
        $afterSelf = $this->createMock(RouteUri::class);
        $afterSelf->expects($this->once())->method('page')->with(3)->willReturnSelf();

        $nav = $this->createMock(Navigator::class);
        $nav->expects($this->once())->method('self')->willReturn($afterSelf);

        $instance = new Pagination($nav, $this->systemUri, $this->rendererService);

        self::assertSame($afterSelf, $instance->toRoute(3));
    }

    #[Test]
    public function toRoute_usesExplicitRouteWhenSet(): void
    {
        $route = $this->createMock(RouteUri::class);

        $route->expects($this->once())
            ->method('withVar')
            ->with('page', 5)
            ->willReturnSelf();

        $nav = $this->createMock(Navigator::class);
        $nav->expects($this->never())->method('self');

        $instance = new Pagination($nav, $this->systemUri, $this->rendererService);
        $instance->route($route);

        self::assertSame($route, $instance->toRoute(5));
    }

    #[Test]
    public function configureNavigator_replacesNavigatorWithCallbackResult(): void
    {
        $newNav = $this->createStub(Navigator::class);

        $this->instance->configureNavigator(fn(Navigator $nav) => $newNav);

        self::assertSame($newNav, $this->instance->nav);
    }

    #[Test]
    public function configureNavigator_keepsOriginalNavigatorWhenCallbackReturnsNull(): void
    {
        $this->instance->configureNavigator(fn(Navigator $nav) => null);

        self::assertSame($this->nav, $this->instance->nav);
    }

    #[Test]
    public function allowQuery_delegatesToNavigatorAndReplacesIt(): void
    {
        $nav = $this->createMock(Navigator::class);
        $nav->expects($this->once())
            ->method('allowQuery')
            ->with(['page'], true)
            ->willReturnSelf();

        $instance = new Pagination($nav, $this->systemUri, $this->rendererService);
        $instance->allowQuery(['page'], true);

        self::assertSame($nav, $instance->nav);
    }

    #[Before]
    protected function prepareInstance(): void
    {
        $this->nav = $this->createStub(Navigator::class);
        $this->systemUri = $this->createStub(SystemUri::class);
        $this->rendererService = $this->createStub(RendererService::class);

        $this->instance = new Pagination($this->nav, $this->systemUri, $this->rendererService);
    }
}
