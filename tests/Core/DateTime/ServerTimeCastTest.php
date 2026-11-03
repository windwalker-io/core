<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\DateTime\ServerTimeCast;
use Windwalker\Core\Runtime\Config;
use Windwalker\Database\DatabaseAdapter;

#[CoversClass(ServerTimeCast::class)]
class ServerTimeCastTest extends TestCase
{
    private function createChronosService(): ChronosService
    {
        $db = $this->createStub(DatabaseAdapter::class);
        $db->method('getDateFormat')->willReturn(Chronos::FORMAT_YMD_HIS);

        return new ChronosService(
            new Config(
                [
                    'app' => [
                        'timezone' => 'Asia/Taipei',
                        'server_timezone' => 'UTC',
                    ],
                ]
            ),
            $db
        );
    }

    #[Test]
    public function hydrateReturnsNullForNullValue(): void
    {
        $cast = new ServerTimeCast();

        self::assertNull($cast->hydrate(null));
    }

    #[Test]
    public function hydrateReturnsWrappedValueWhenToLocalIsFalse(): void
    {
        $cast = new ServerTimeCast(toLocal: false);

        $result = $cast->hydrate('2020-01-01 10:00:00');

        self::assertInstanceOf(Chronos::class, $result);
        self::assertSame('2020-01-01 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function hydrateConvertsToStaticTimezoneWhenGiven(): void
    {
        $cast = new ServerTimeCast(toLocal: 'Asia/Tokyo');

        $result = $cast->hydrate('2020-01-01 10:00:00');

        self::assertSame('Asia/Tokyo', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 19:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function hydrateConvertsToServiceLocalTimezoneWhenTrue(): void
    {
        $cast = new ServerTimeCast(toLocal: true, chronosService: $this->createChronosService());

        $result = $cast->hydrate('2020-01-01 02:00:00');

        self::assertSame('Asia/Taipei', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function hydrateThrowsWhenToLocalIsTrueWithoutChronosService(): void
    {
        $cast = new ServerTimeCast(toLocal: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/toLocal is TRUE/');

        $cast->hydrate('2020-01-01 10:00:00');
    }

    #[Test]
    public function extractReturnsNullForNullValue(): void
    {
        $cast = new ServerTimeCast();

        self::assertNull($cast->extract(null));
    }

    #[Test]
    public function extractReturnsStringAsIsWhenNotDateTimeInterface(): void
    {
        $cast = new ServerTimeCast();

        self::assertSame('plain-string', $cast->extract('plain-string'));
    }

    #[Test]
    public function extractConvertsToStaticTimezoneAndDefaultFormat(): void
    {
        $cast = new ServerTimeCast(toServer: 'UTC');

        $result = $cast->extract(new Chronos('2020-01-01 10:00:00', 'Asia/Tokyo'));

        self::assertSame('2020-01-01 01:00:00', $result);
    }

    #[Test]
    public function extractConvertsToServiceServerTimezoneAndSqlFormatWhenTrue(): void
    {
        $cast = new ServerTimeCast(toServer: true, chronosService: $this->createChronosService());

        $result = $cast->extract(new Chronos('2020-01-01 10:00:00', 'Asia/Taipei'));

        self::assertSame('2020-01-01 02:00:00', $result);
    }

    #[Test]
    public function extractThrowsWhenToServerIsTrueWithoutChronosService(): void
    {
        $cast = new ServerTimeCast(toServer: true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/toServer is TRUE/');

        $cast->extract(new Chronos('2020-01-01 10:00:00', 'UTC'));
    }

    #[Test]
    public function extractKeepsOriginalTimezoneWhenToServerIsFalse(): void
    {
        $cast = new ServerTimeCast(toServer: false);

        $result = $cast->extract(new Chronos('2020-01-01 10:00:00', 'Asia/Tokyo'));

        self::assertSame('2020-01-01 10:00:00', $result);
    }
}
