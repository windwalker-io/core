<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Schedule;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Schedule\ScheduleEvent;

#[CoversClass(ScheduleEvent::class)]
class ScheduleEventTest extends TestCase
{
    #[Test]
    public function executeInvokesHandlerAndReturnsItsResult(): void
    {
        $event = new ScheduleEvent('demo', '* * * * *', fn() => 'executed');

        self::assertSame('executed', $event->execute());
    }

    #[Test]
    public function getHandlerFallsBackToNoOpClosureWhenUnset(): void
    {
        $event = new ScheduleEvent('demo');

        $handler = $event->getHandler();

        self::assertInstanceOf(\Closure::class, $handler);
        self::assertNull($handler());
    }

    #[Test]
    public function isDueReturnsTrueWhenExpressionMatchesGivenClock(): void
    {
        // Daily job, due at midnight.
        $event = new ScheduleEvent('demo', '@daily', fn() => null);

        self::assertTrue($event->isDue('2024-01-01 00:00:00'));
    }

    #[Test]
    public function isDueReturnsFalseWhenExpressionDoesNotMatchGivenClock(): void
    {
        $event = new ScheduleEvent('demo', '@daily', fn() => null);

        self::assertFalse($event->isDue('2024-01-01 08:30:00'));
    }

    #[Test]
    public function isDueAcceptsDateTimeZoneInstance(): void
    {
        $event = new ScheduleEvent('demo', '@daily', fn() => null);

        self::assertTrue($event->isDue('2024-01-01 00:00:00', new \DateTimeZone('UTC')));
    }

    #[Test]
    public function tagsMergesAndDeduplicatesAccumulatedTags(): void
    {
        $event = new ScheduleEvent('demo');

        $event->tags('a', 'b');
        $event->tags('b', 'c');

        self::assertSame(['a', 'b', 'c'], array_values($event->getTags()));
    }

    #[Test]
    public function tagsReturnsSelfForChaining(): void
    {
        $event = new ScheduleEvent('demo');

        self::assertSame($event, $event->tags('a'));
    }
}
