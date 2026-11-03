<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\View\CollapseWrapper;

#[CoversClass(CollapseWrapper::class)]
class CollapseWrapperTest extends TestCase
{
    #[Test]
    public function invokeMergesAllConstructorArguments(): void
    {
        $wrapper = new CollapseWrapper(['a' => 1], ['b' => 2], ['a' => 3]);

        self::assertSame(['a' => 3, 'b' => 2], $wrapper());
    }

    #[Test]
    public function invokeIgnoresTheProvidedSourceArgument(): void
    {
        $wrapper = new CollapseWrapper(['x' => 1]);

        self::assertSame(['x' => 1], $wrapper('whatever source value is passed in'));
    }

    #[Test]
    public function argsPropertyStoresRawConstructorArguments(): void
    {
        $wrapper = new CollapseWrapper(['a' => 1], ['b' => 2]);

        self::assertSame([['a' => 1], ['b' => 2]], $wrapper->args);
    }
}
