<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\State\Persistence;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\State\Persistence\CookiesPersistence;
use Windwalker\Session\Cookie\CookiesInterface;

class CookiesPersistenceTest extends TestCase
{
    #[Test]
    public function getDelegatesToCookies(): void
    {
        $cookies = $this->createStub(CookiesInterface::class);
        $cookies->method('get')->willReturn('bar');

        self::assertSame('bar', new CookiesPersistence($cookies)->get('foo'));
    }

    #[Test]
    public function storeSetsValueOnCookiesAndReturnsIt(): void
    {
        $cookies = $this->createMock(CookiesInterface::class);
        $cookies->expects($this->once())->method('set')->with('foo', 'bar');

        self::assertSame('bar', new CookiesPersistence($cookies)->store('foo', 'bar'));
    }

    #[Test]
    public function forgetRemovesValueFromCookies(): void
    {
        $cookies = $this->createMock(CookiesInterface::class);
        $cookies->expects($this->once())->method('remove')->with('foo');

        new CookiesPersistence($cookies)->forget('foo');
    }

    #[Test]
    public function allWrapsCookiesStorageInIterator(): void
    {
        $cookies = $this->createStub(CookiesInterface::class);
        $cookies->method('getStorage')->willReturn(['a' => '1', 'b' => '2']);

        self::assertSame(
            ['a' => '1', 'b' => '2'],
            iterator_to_array(new CookiesPersistence($cookies)->all())
        );
    }
}
