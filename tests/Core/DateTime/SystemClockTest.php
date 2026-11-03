<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\SystemClock;

#[CoversClass(SystemClock::class)]
class SystemClockTest extends TestCase
{
    #[Test]
    public function nowReturnsChronosRepresentingCurrentTime(): void
    {
        $clock = new SystemClock();

        $before = time();
        $result = $clock->now();
        $after = time();

        self::assertInstanceOf(Chronos::class, $result);
        self::assertGreaterThanOrEqual($before, $result->getTimestamp());
        self::assertLessThanOrEqual($after, $result->getTimestamp());
    }
}
