<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Package;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Package\InstallResource;

class InstallResourceTest extends TestCase
{
    #[Test]
    public function constructorSetsTheTag(): void
    {
        $resource = new InstallResource('my-tag');

        self::assertSame('my-tag', $resource->tag);
    }

    #[Test]
    public function constructorDefaultsTagToNull(): void
    {
        $resource = new InstallResource();

        self::assertNull($resource->tag);
    }

    #[Test]
    public function addMergesDataIntoTheNamedProperty(): void
    {
        $resource = new InstallResource();

        $resource->add('config', ['a' => '1']);
        $resource->add('config', ['b' => '2']);

        self::assertSame(['a' => '1', 'b' => '2'], $resource->config);
    }

    #[Test]
    public function addReturnsSelfForChaining(): void
    {
        $resource = new InstallResource();

        self::assertSame($resource, $resource->add('config', []));
    }

    #[Test]
    public function addOverwritesSameKeysInLaterCalls(): void
    {
        $resource = new InstallResource();

        $resource->add('files', ['src/a.php' => 'dest/a.php']);
        $resource->add('files', ['src/a.php' => 'dest/a-renamed.php']);

        self::assertSame(['src/a.php' => 'dest/a-renamed.php'], $resource->files);
    }

    #[Test]
    public function dumpReturnsOnlyResourceProperties(): void
    {
        $resource = new InstallResource('my-tag');
        $resource->add('config', ['a' => '1']);
        $resource->add('modules', ['m' => 'n']);
        $resource->addCallback(static fn() => null);

        self::assertSame(
            [
                'config' => ['a' => '1'],
                'migrations' => [],
                'seeders' => [],
                'languages' => [],
                'routes' => [],
                'views' => [],
                'modules' => ['m' => 'n'],
                'files' => [],
            ],
            $resource->dump()
        );
    }
}
