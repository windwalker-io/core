<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\TimeZoneSerializer;

#[CoversClass(TimeZoneSerializer::class)]
class TimeZoneSerializerTest extends TestCase
{
    #[Test]
    public function serializeReturnsNullForNullValue(): void
    {
        $serializer = new TimeZoneSerializer();

        self::assertNull($serializer->serialize(null));
    }

    #[Test]
    public function serializeConvertsToDefaultUtcTimezone(): void
    {
        $serializer = new TimeZoneSerializer();

        $result = $serializer->serialize(new Chronos('2020-01-01 10:00:00', 'Asia/Taipei'));

        self::assertInstanceOf(Chronos::class, $result);
        self::assertSame('UTC', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 02:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function serializeConvertsToConfiguredStringTimezone(): void
    {
        $serializer = new TimeZoneSerializer('Asia/Tokyo');

        $result = $serializer->serialize(new Chronos('2020-01-01 10:00:00', 'Asia/Tokyo'));

        self::assertSame('Asia/Tokyo', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function serializeConvertsToConfiguredTimezoneObject(): void
    {
        $serializer = new TimeZoneSerializer(new DateTimeZone('Asia/Tokyo'));

        $result = $serializer->serialize(new Chronos('2020-01-01 10:00:00', 'UTC'));

        self::assertSame('Asia/Tokyo', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 19:00:00', $result->format('Y-m-d H:i:s'));
    }
}
