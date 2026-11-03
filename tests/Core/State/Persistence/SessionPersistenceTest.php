<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\State\Persistence;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\State\Persistence\SessionPersistence;
use Windwalker\Session\SessionInterface;

class SessionPersistenceTest extends TestCase
{
    #[Test]
    public function getDelegatesToSession(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->willReturn('bar');

        self::assertSame('bar', new SessionPersistence($session)->get('foo'));
    }

    #[Test]
    public function storeSetsValueOnSessionAndReturnsIt(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('set')->with('foo', 'bar');

        self::assertSame('bar', new SessionPersistence($session)->store('foo', 'bar'));
    }

    #[Test]
    public function forgetRemovesValueFromSession(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('remove')->with('foo');

        new SessionPersistence($session)->forget('foo');
    }

    #[Test]
    public function allWrapsSessionDumpInIterator(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('dump')->willReturn(['a' => 1, 'b' => 2]);

        self::assertSame(
            ['a' => 1, 'b' => 2],
            iterator_to_array(new SessionPersistence($session)->all())
        );
    }
}
