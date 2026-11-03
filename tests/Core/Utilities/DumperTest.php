<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Utilities;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Server\Connection;
use Windwalker\Core\Utilities\Dumper;

/**
 * Only handleValues() and the getConnection()/setConnection() wiring are
 * covered here. Dumper's constructor builds a real Symfony VarDumper
 * Connection, but Connection only opens a socket lazily inside write(), so
 * constructing a Dumper is safe without a running dump server as long as
 * write()/dump() are never invoked with the real connection.
 */
class DumperTest extends TestCase
{
    private function callHandleValues(Dumper $dumper, array $args): mixed
    {
        $method = new ReflectionMethod($dumper, 'handleValues');

        return $method->invoke($dumper, $args);
    }

    #[Test]
    public function handleValuesReturnsSingleValueDirectly(): void
    {
        $dumper = new Dumper();

        self::assertSame('foo', $this->callHandleValues($dumper, ['foo']));
    }

    #[Test]
    public function handleValuesLabelsMultipleListValues(): void
    {
        $dumper = new Dumper();

        self::assertSame(
            ['Value 1' => 'foo', 'Value 2' => 'bar'],
            $this->callHandleValues($dumper, ['foo', 'bar'])
        );
    }

    #[Test]
    public function handleValuesReturnsAssociativeArrayAsIs(): void
    {
        $dumper = new Dumper();

        self::assertSame(
            ['a' => 1, 'b' => 2],
            $this->callHandleValues($dumper, ['a' => 1, 'b' => 2])
        );
    }

    #[Test]
    public function getAndSetConnectionRoundTrips(): void
    {
        $dumper = new Dumper();
        $connection = $this->createStub(Connection::class);

        $dumper->setConnection($connection);

        self::assertSame($connection, $dumper->getConnection());
    }

    #[Test]
    public function dumpToServerWritesClonedDataToConnection(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('write')
            ->with(self::isInstanceOf(Data::class))
            ->willReturn(true);

        $dumper = new Dumper();
        $dumper->setConnection($connection);

        self::assertTrue($dumper->dumpToServer('foo'));
    }
}
