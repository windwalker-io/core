<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Pagination;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Pagination\PageItem;
use Windwalker\Core\Router\RouteUri;

class PageItemTest extends TestCase
{
    #[Test]
    public function toLink_castsFullRouteToString(): void
    {
        $route = $this->createStub(RouteUri::class);
        $route->method('full')->willReturn($route);
        $route->method('__toString')->willReturn('https://example.com/page/2');

        $item = new PageItem('current', 2, $route);

        self::assertSame('https://example.com/page/2', $item->toLink());
    }

    #[Test]
    public function toString_isAliasOfToLink(): void
    {
        $route = $this->createStub(RouteUri::class);
        $route->method('full')->willReturn($route);
        $route->method('__toString')->willReturn('https://example.com/page/3');

        $item = new PageItem('next', 3, $route);

        self::assertSame($item->toLink(), (string) $item);
    }

    #[Test]
    public function jsonSerialize_returnsObjectVars(): void
    {
        $item = new PageItem('current', 2, null);

        self::assertEquals(
            ['name' => 'current', 'page' => 2, 'route' => null],
            $item->jsonSerialize()
        );
    }
}
