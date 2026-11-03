<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Renderer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Renderer\LayoutNotFoundException;
use Windwalker\Core\Renderer\LayoutPathResolver;
use Windwalker\Core\Renderer\PathsBag;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\DI\Container;
use Windwalker\Filesystem\Path;
use Windwalker\Renderer\CompositeRenderer;
use Windwalker\Renderer\RendererInterface;

#[CoversClass(RendererService::class)]
class RendererServiceTest extends TestCase
{
    #[Test]
    public function renderWithMergesGlobalsIntoDataAndRendersResolvedLayout(): void
    {
        $pathResolver = $this->createMock(LayoutPathResolver::class);
        $pathResolver->expects($this->once())
            ->method('resolveLayout')
            ->with('hello', [])
            ->willReturn('/resolved/hello.php');

        $service = new RendererService(new Container(), $pathResolver, ['app_name' => 'Acme']);

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with('/resolved/hello.php', ['app_name' => 'Acme', 'title' => 'Hi'], ['foo' => 'bar'])
            ->willReturn('OUTPUT');

        self::assertSame(
            'OUTPUT',
            $service->renderWith($renderer, 'hello', ['title' => 'Hi'], ['foo' => 'bar'])
        );
    }

    #[Test]
    public function renderWithLetsExplicitDataOverrideGlobals(): void
    {
        $pathResolver = $this->createStub(LayoutPathResolver::class);
        $pathResolver->method('resolveLayout')->willReturn('/resolved/hello.php');

        $service = new RendererService(new Container(), $pathResolver, ['title' => 'Default']);

        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with(self::anything(), ['title' => 'Overridden'], []);

        $service->renderWith($renderer, 'hello', ['title' => 'Overridden']);
    }

    #[Test]
    public function createRendererResolvesCompositeRendererWithoutExtraPaths(): void
    {
        $renderer = $this->createMock(RendererInterface::class);
        $renderer->expects($this->never())->method($this->anything());

        $container = $this->createMock(Container::class);
        $container->expects($this->once())
            ->method('resolve')
            ->with(CompositeRenderer::class)
            ->willReturn($renderer);

        $service = new RendererService($container, $this->createStub(LayoutPathResolver::class));

        self::assertSame($renderer, $service->createRenderer());
    }

    #[Test]
    public function createRendererAddsEachExtraPathToResolvedRenderer(): void
    {
        $calledWith = [];

        $renderer = $this->getMockBuilder(CompositeRenderer::class)
            ->onlyMethods(['addPath'])
            ->getMock();
        $renderer->expects($this->exactly(2))
            ->method('addPath')
            ->willReturnCallback(
                function (string|array $path) use ($renderer, &$calledWith) {
                    $calledWith[] = $path;

                    return $renderer;
                }
            );

        $container = $this->createStub(Container::class);
        $container->method('resolve')->willReturn($renderer);

        $service = new RendererService($container, $this->createStub(LayoutPathResolver::class));

        self::assertSame($renderer, $service->createRenderer(['/a', '/b']));
        self::assertSame(['/a', '/b'], $calledWith);
    }

    #[Test]
    public function getSupportedExtensionsFlattensFactoryExtensionsFromContainerParam(): void
    {
        $container = new Container();
        $container->setParameters(
            [
                'renderer' => [
                    'renderers' => [
                        'edge' => ['EdgeRendererClass', ['edge.php']],
                        'php' => ['PhpRendererClass', ['php', 'phtml']],
                    ],
                ],
            ]
        );

        $service = new RendererService($container, $this->createStub(LayoutPathResolver::class));

        self::assertSame(['edge.php', 'php', 'phtml'], $service->getSupportedExtensions());
    }

    #[Test]
    public function getSupportedExtensionsReturnsEmptyArrayWhenNoFactoriesConfigured(): void
    {
        $service = new RendererService(new Container(), $this->createStub(LayoutPathResolver::class));

        self::assertSame([], $service->getSupportedExtensions());
    }

    #[Test]
    public function resolveLayoutIfExistsReturnsResolvedPathWhenFound(): void
    {
        $pathResolver = $this->createStub(LayoutPathResolver::class);
        $pathResolver->method('resolveLayout')->willReturn('/resolved/hello.php');

        $service = new RendererService(new Container(), $pathResolver);

        self::assertSame('/resolved/hello.php', $service->resolveLayoutIfExists('hello'));
    }

    #[Test]
    public function resolveLayoutIfExistsReturnsNullWhenLayoutNotFound(): void
    {
        $pathResolver = $this->createStub(LayoutPathResolver::class);
        $pathResolver->method('resolveLayout')->willThrowException(new LayoutNotFoundException('not found'));

        $service = new RendererService(new Container(), $pathResolver);

        self::assertNull($service->resolveLayoutIfExists('missing'));
    }

    #[Test]
    public function hasLayoutReturnsTrueWhenLayoutIsResolvable(): void
    {
        $pathResolver = $this->createStub(LayoutPathResolver::class);
        $pathResolver->method('resolveLayout')->willReturn('/resolved/hello.php');

        $service = new RendererService(new Container(), $pathResolver);

        self::assertTrue($service->hasLayout('hello'));
    }

    #[Test]
    public function hasLayoutReturnsFalseWhenLayoutNotFound(): void
    {
        $pathResolver = $this->createStub(LayoutPathResolver::class);
        $pathResolver->method('resolveLayout')->willThrowException(new LayoutNotFoundException('not found'));

        $service = new RendererService(new Container(), $pathResolver);

        self::assertFalse($service->hasLayout('missing'));
    }

    #[Test]
    public function addPathDelegatesToPathResolverAddPathsAndReturnsSelf(): void
    {
        $pathResolver = $this->createMock(LayoutPathResolver::class);
        $pathResolver->expects($this->once())
            ->method('addPaths')
            ->with(['/foo'], 123, 'ns1');

        $service = new RendererService(new Container(), $pathResolver);

        self::assertSame($service, $service->addPath(['/foo'], 123, 'ns1'));
    }

    #[Test]
    public function dumpPathsConvertsPathsBagIntoArrayInInsertionOrder(): void
    {
        $bag = new PathsBag();
        $bag->addPath(['/a', '/b']);

        $pathResolver = $this->createMock(LayoutPathResolver::class);
        $pathResolver->expects($this->once())
            ->method('getPathsBag')
            ->with('main')
            ->willReturn($bag);

        $service = new RendererService(new Container(), $pathResolver);

        self::assertSame(
            [Path::normalize('/a'), Path::normalize('/b')],
            array_values($service->dumpPaths())
        );
    }

    #[Test]
    public function dumpAllPathsIteratesEveryNamespaceBag(): void
    {
        $mainBag = new PathsBag();
        $mainBag->addPath('/main');

        $themeBag = new PathsBag();
        $themeBag->addPath('/theme');

        $pathResolver = $this->createStub(LayoutPathResolver::class);
        $pathResolver->method('getPathsBags')->willReturn(['main' => $mainBag, 'theme' => $themeBag]);

        $service = new RendererService(new Container(), $pathResolver);

        self::assertSame(
            [
                'main' => [Path::normalize('/main')],
                'theme' => [Path::normalize('/theme')],
            ],
            $service->dumpAllPaths()
        );
    }
}
