<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Schedule;

use BadMethodCallException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\ScheduleEvent;

#[CoversClass(Schedule::class)]
class ScheduleTest extends TestCase
{
    #[Test]
    public function cronRegistersAndReturnsScheduleEvent(): void
    {
        $schedule = new Schedule();

        $event = $schedule->cron('demo', '* * * * *', fn() => null);

        self::assertInstanceOf(ScheduleEvent::class, $event);
        self::assertSame(['demo' => $event], iterator_to_array($schedule->getEvents()));
    }

    #[Test]
    public function getEventsWithoutTagsReturnsAllRegisteredEvents(): void
    {
        $schedule = new Schedule();

        $schedule->cron('a', '* * * * *', fn() => null);
        $schedule->cron('b', '* * * * *', fn() => null);

        self::assertSame(['a', 'b'], array_keys(iterator_to_array($schedule->getEvents())));
    }

    #[Test]
    public function getEventsFiltersByExactTagSetIntersection(): void
    {
        $schedule = new Schedule();

        $schedule->cron('a', '* * * * *', fn() => null)->tags('foo', 'bar');
        $schedule->cron('b', '* * * * *', fn() => null)->tags('foo');

        $matched = iterator_to_array($schedule->getEvents(['foo', 'bar']));

        self::assertSame(['a'], array_keys($matched));
    }

    #[Test]
    public function getDueEventsYieldsOnlyEventsDueAtGivenClock(): void
    {
        $schedule = new Schedule();

        $schedule->cron('daily-job', '@daily', fn() => null);
        $schedule->cron('hourly-not-due', '0 8 * * *', fn() => null);

        $due = iterator_to_array($schedule->getDueEvents([], '2024-01-01 00:00:00'));

        self::assertSame(['daily-job'], array_keys($due));
    }

    #[Test]
    public function magicCallDispatchesKnownExpressionAliases(): void
    {
        $schedule = new Schedule();

        $event = $schedule->daily('demo');

        self::assertInstanceOf(ScheduleEvent::class, $event);
        self::assertSame('0 0 * * *', (string) $event);
    }

    #[Test]
    public function magicCallThrowsBadMethodCallExceptionForUnknownMethod(): void
    {
        $schedule = new Schedule();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageIs('Call to undefined method ' . Schedule::class . '::notAMethod()');

        $schedule->notAMethod('demo');
    }
}
