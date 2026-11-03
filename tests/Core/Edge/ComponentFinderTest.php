<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Edge;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Edge\ComponentFinder;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Utilities\ClassFinder;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Tests\Core\Edge\Stub\ComponentWithAttributeStub;
use Windwalker\Tests\Core\Edge\Stub\PlainClassStub;

class ComponentFinderTest extends TestCase
{
    private string $tmpDir;

    #[Before]
    protected function prepareTempDir(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/windwalker-component-finder-test-' . uniqid('', true);

        mkdir($this->tmpDir, 0777, true);
    }

    #[After]
    protected function removeTempDir(): void
    {
        if (is_dir($this->tmpDir)) {
            Filesystem::delete($this->tmpDir);
        }
    }

    private function createApp(bool $debug, array $scans): ApplicationInterface
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('isDebug')->willReturn($debug);
        $app->method('config')->willReturnCallback(
            static fn(string $name, ?string $delimiter = '.') => $name === 'renderer.edge.component_scans'
                ? $scans
                : null
        );

        return $app;
    }

    private function createPathResolver(): PathResolver
    {
        return new PathResolver(new Config(['@cache' => $this->tmpDir]));
    }

    #[Test]
    public function findScansAndKeepsOnlyClassesWithEdgeComponentAttribute(): void
    {
        $classFinder = $this->createStub(ClassFinder::class);
        $classFinder->method('findClasses')
            ->willReturnCallback(
                static function (string $ns, bool $recursive) {
                    self::assertSame('Windwalker\\Tests\\Core\\Edge\\Stub', $ns);
                    self::assertTrue($recursive);

                    yield ComponentWithAttributeStub::class;
                    yield PlainClassStub::class;
                }
            );

        $finder = new ComponentFinder(
            $this->createApp(debug: true, scans: ['Windwalker\\Tests\\Core\\Edge\\Stub']),
            $classFinder,
            $this->createPathResolver()
        );

        self::assertSame(
            ['stub-component' => ComponentWithAttributeStub::class],
            $finder->find()
        );
    }

    #[Test]
    public function findAlwaysRescansWhenAppIsInDebugMode(): void
    {
        $classFinder = $this->createMock(ClassFinder::class);
        $classFinder->expects($this->exactly(2))
            ->method('findClasses')
            ->willReturnCallback(
                static function () {
                    yield ComponentWithAttributeStub::class;
                }
            );

        $finder = new ComponentFinder(
            $this->createApp(debug: true, scans: ['Windwalker\\Tests\\Core\\Edge\\Stub']),
            $classFinder,
            $this->createPathResolver()
        );

        $finder->find();
        $finder->find();
    }

    #[Test]
    public function findCachesScanResultWhenAppIsNotInDebugMode(): void
    {
        $classFinder = $this->createMock(ClassFinder::class);
        $classFinder->expects($this->once())
            ->method('findClasses')
            ->willReturnCallback(
                static function () {
                    yield ComponentWithAttributeStub::class;
                }
            );

        $pathResolver = $this->createPathResolver();
        $app = $this->createApp(debug: false, scans: ['Windwalker\\Tests\\Core\\Edge\\Stub']);

        // Two separate finder instances sharing the same cache directory;
        // the second one must read the cached result instead of rescanning.
        $first = new ComponentFinder($app, $classFinder, $pathResolver);
        $second = new ComponentFinder($app, $classFinder, $pathResolver);

        $expected = ['stub-component' => ComponentWithAttributeStub::class];

        self::assertSame($expected, $first->find());
        self::assertSame($expected, $second->find());
    }

    #[Test]
    public function getCachePathResolvesUnderCacheAlias(): void
    {
        $finder = new ComponentFinder(
            $this->createApp(debug: false, scans: []),
            $this->createStub(ClassFinder::class),
            $this->createPathResolver()
        );

        self::assertSame(
            $this->tmpDir . '/renderer/components',
            $finder->getCachePath()
        );
    }

    #[Test]
    public function getCacheReturnsSameInstanceOnSubsequentCalls(): void
    {
        $finder = new ComponentFinder(
            $this->createApp(debug: false, scans: []),
            $this->createStub(ClassFinder::class),
            $this->createPathResolver()
        );

        self::assertSame($finder->getCache(), $finder->getCache());
    }
}
