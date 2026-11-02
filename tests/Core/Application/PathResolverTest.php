<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application;

use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Runtime\Config;

class PathResolverTest extends TestCase
{
    private function create(): PathResolver
    {
        return new PathResolver(
            new Config([
                '@root' => '/var/www',
                '@cache' => '/var/www/cache',
                'other' => 'x',
            ])
        );
    }

    #[Test]
    public function all(): void
    {
        self::assertSame(
            ['@root' => '/var/www', '@cache' => '/var/www/cache'],
            $this->create()->all()
        );
    }

    #[Test]
    public function resolveAliasOnly(): void
    {
        $r = $this->create();

        self::assertSame('/var/www', $r->resolve('@root'));
        self::assertNull($r->resolve('@none'));
    }

    #[Test]
    public function resolveAliasWithSubPath(): void
    {
        $r = $this->create();

        self::assertSame('/var/www/cache/foo/bar', $r->resolve('@cache/foo/bar'));
        self::assertSame('/var/www/cache/foo/bar', $r->resolve('@cache\\foo\\bar'));
    }

    #[Test]
    public function resolveUnknownAliasWithSubPathThrows(): void
    {
        $this->expectException(LogicException::class);

        $this->create()->resolve('@none/foo');
    }

    #[Test]
    public function resolveRelativeUsesRoot(): void
    {
        self::assertSame('/var/www' . DIRECTORY_SEPARATOR . 'foo/bar', $this->create()->resolve('foo/bar'));
    }

    #[Test]
    public function resolveAbsoluteReturnedAsIs(): void
    {
        self::assertSame('/etc/hosts', $this->create()->resolve('/etc/hosts'));
    }

    #[Test]
    public function addBase(): void
    {
        $r = $this->create();

        self::assertSame('/tmp/a', $r->addBase('/tmp/a'));
        self::assertSame('/base' . DIRECTORY_SEPARATOR . 'a', $r->addBase('a', '/base'));
        self::assertSame('/var/www/cache' . DIRECTORY_SEPARATOR . 'a', $r->addBase('a', '@cache'));
    }
}
