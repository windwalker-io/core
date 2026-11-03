<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Edge;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Edge\CoreFileLoader;
use Windwalker\Core\Renderer\LayoutPathResolver;
use Windwalker\Edge\Exception\LayoutNotFoundException;
use Windwalker\Edge\Loader\EdgeFileLoader;

class CoreFileLoaderTest extends TestCase
{
    #[Test]
    public function findResolvesLayoutThroughPathResolver(): void
    {
        $pathResolver = $this->createMock(LayoutPathResolver::class);
        $pathResolver->expects($this->once())
            ->method('resolveLayout')
            ->with('hello.world', ['edge.php'])
            ->willReturn('/path/to/hello.world.edge.php');

        $loader = $this->createStub(EdgeFileLoader::class);

        $coreLoader = new CoreFileLoader($loader, $pathResolver, ['edge.php']);

        self::assertSame('/path/to/hello.world.edge.php', $coreLoader->find('hello.world'));
    }

    #[Test]
    public function findCachesResolvedPathPerKey(): void
    {
        $pathResolver = $this->createMock(LayoutPathResolver::class);
        $pathResolver->expects($this->once())
            ->method('resolveLayout')
            ->willReturn('/path/to/hello.world.edge.php');

        $loader = $this->createStub(EdgeFileLoader::class);
        $coreLoader = new CoreFileLoader($loader, $pathResolver);

        // Second call must be served from the instance cache, so
        // resolveLayout() is only expected to run once (see expects() above).
        self::assertSame('/path/to/hello.world.edge.php', $coreLoader->find('hello.world'));
        self::assertSame('/path/to/hello.world.edge.php', $coreLoader->find('hello.world'));
    }

    #[Test]
    public function findFallsBackToEdgeFileLoaderWhenPathResolverMisses(): void
    {
        $pathResolver = $this->createStub(LayoutPathResolver::class);
        $pathResolver->method('resolveLayout')
            ->willThrowException(new LayoutNotFoundException('not found'));

        $loader = $this->createMock(EdgeFileLoader::class);
        $loader->expects($this->once())->method('has')->with('hello.world')->willReturn(true);
        $loader->expects($this->once())
            ->method('find')
            ->with('hello.world')
            ->willReturn('/legacy/hello.world.edge.php');

        $coreLoader = new CoreFileLoader($loader, $pathResolver);

        self::assertSame('/legacy/hello.world.edge.php', $coreLoader->find('hello.world'));
    }

    #[Test]
    public function findThrowsLayoutNotFoundExceptionWhenNothingMatches(): void
    {
        $pathResolver = $this->createStub(LayoutPathResolver::class);
        $pathResolver->method('resolveLayout')
            ->willThrowException(new LayoutNotFoundException('Unable to find layout: hello.world'));

        $loader = $this->createStub(EdgeFileLoader::class);
        $loader->method('has')->willReturn(false);

        $coreLoader = new CoreFileLoader($loader, $pathResolver);

        $this->expectException(LayoutNotFoundException::class);
        $this->expectExceptionMessageIsOrContains('Unable to find layout: hello.world');

        $coreLoader->find('hello.world');
    }

    #[Test]
    public function hasReturnsTrueWhenFindSucceeds(): void
    {
        $pathResolver = $this->createStub(LayoutPathResolver::class);
        $pathResolver->method('resolveLayout')->willReturn('/path/to/hello.world.edge.php');

        $coreLoader = new CoreFileLoader($this->createStub(EdgeFileLoader::class), $pathResolver);

        self::assertTrue($coreLoader->has('hello.world'));
    }

    #[Test]
    public function hasReturnsFalseWhenLayoutNotFound(): void
    {
        $pathResolver = $this->createStub(LayoutPathResolver::class);
        $pathResolver->method('resolveLayout')->willThrowException(new LayoutNotFoundException('missing'));

        $loader = $this->createStub(EdgeFileLoader::class);
        $loader->method('has')->willReturn(false);

        $coreLoader = new CoreFileLoader($loader, $pathResolver);

        self::assertFalse($coreLoader->has('missing.layout'));
    }

    #[Test]
    public function loadDelegatesToEdgeFileLoader(): void
    {
        $loader = $this->createMock(EdgeFileLoader::class);
        $loader->expects($this->once())
            ->method('load')
            ->with('/path/to/hello.world.edge.php')
            ->willReturn('<div>Hello</div>');

        $coreLoader = new CoreFileLoader($loader, $this->createStub(LayoutPathResolver::class));

        self::assertSame('<div>Hello</div>', $coreLoader->load('/path/to/hello.world.edge.php'));
    }
}
