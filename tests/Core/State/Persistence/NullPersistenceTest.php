<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\State\Persistence;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\State\Persistence\NullPersistence;

class NullPersistenceTest extends TestCase
{
    #[Test]
    public function getAlwaysReturnsNull(): void
    {
        self::assertNull(new NullPersistence()->get('any.key'));
    }

    #[Test]
    public function storeReturnsValueWithoutPersistingIt(): void
    {
        $persistence = new NullPersistence();

        self::assertSame('value', $persistence->store('any.key', 'value'));
        self::assertNull($persistence->get('any.key'));
    }

    #[Test]
    public function forgetIsNoOp(): void
    {
        $persistence = new NullPersistence();

        $persistence->forget('any.key');

        self::assertNull($persistence->get('any.key'));
    }

    #[Test]
    public function allReturnsEmptyIterator(): void
    {
        self::assertSame([], iterator_to_array(new NullPersistence()->all()));
    }
}
