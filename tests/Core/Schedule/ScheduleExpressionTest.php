<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Schedule;

use Cron\CronExpression;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Schedule\ScheduleExpression;

#[CoversClass(ScheduleExpression::class)]
class ScheduleExpressionTest extends TestCase
{
    #[Test]
    public function constructDefaultsToEveryMinuteExpression(): void
    {
        $expr = new ScheduleExpression();

        self::assertSame('* * * * *', (string) $expr);
    }

    #[Test]
    public function constructAcceptsStringExpression(): void
    {
        $expr = new ScheduleExpression('0 0 1 1 *');

        self::assertSame('0 0 1 1 *', (string) $expr);
    }

    #[Test]
    public function constructAcceptsCronExpressionInstance(): void
    {
        $cron = new CronExpression('0 5 * * *');

        $expr = new ScheduleExpression($cron);

        self::assertSame($cron, $expr->getExpression());
    }

    #[Test]
    public function setExpressionConvertsAlwaysKeywordToEveryMinute(): void
    {
        $expr = new ScheduleExpression();

        $expr->setExpression('@always');

        self::assertSame('* * * * *', (string) $expr);
    }

    #[Test]
    public function everyMinutesWithStepAndRangeBuildsMinuteField(): void
    {
        $expr = new ScheduleExpression()->everyMinutes(5, 10, 50);

        self::assertSame('10-50/5 * * * *', (string) $expr);
    }

    #[Test]
    public function minuteOfHourSetsExplicitMinuteList(): void
    {
        $expr = new ScheduleExpression()->minuteOfHour(5, 10);

        self::assertSame('5,10 * * * *', (string) $expr);
    }

    #[Test]
    public function everyHoursWithoutArgumentsResetsMinuteToZero(): void
    {
        $expr = new ScheduleExpression()->everyHours();

        self::assertSame('0 * * * *', (string) $expr);
    }

    #[Test]
    public function everyHoursWithStepBuildsHourField(): void
    {
        $expr = new ScheduleExpression()->everyHours(2);

        self::assertSame('0 */2 * * *', (string) $expr);
    }

    #[Test]
    public function hourOfDaySetsExplicitHourListAndResetsMinute(): void
    {
        $expr = new ScheduleExpression()->hourOfDay(3, 6);

        self::assertSame('0 3,6 * * *', (string) $expr);
    }

    #[Test]
    public function everyDaysWithoutArgumentsResetsHourAndMinute(): void
    {
        $expr = new ScheduleExpression()->everyDays();

        self::assertSame('0 0 * * *', (string) $expr);
    }

    #[Test]
    public function dayOfMonthSetsExplicitDayListAndResetsHourAndMinute(): void
    {
        $expr = new ScheduleExpression()->dayOfMonth(1, 15);

        self::assertSame('0 0 1,15 * *', (string) $expr);
    }

    #[Test]
    public function everyMonthsWithoutArgumentsResetsDayHourAndMinute(): void
    {
        $expr = new ScheduleExpression()->everyMonths();

        self::assertSame('0 0 1 * *', (string) $expr);
    }

    #[Test]
    public function monthOfYearSetsExplicitMonthListAndResetsDayHourAndMinute(): void
    {
        $expr = new ScheduleExpression()->monthOfYear(3, 6);

        self::assertSame('0 0 1 3,6 *', (string) $expr);
    }

    #[Test]
    public function everyWeeksWithStepBuildsWeekField(): void
    {
        $expr = new ScheduleExpression()->everyWeeks(2);

        self::assertSame('0 0 * * */2', (string) $expr);
    }

    #[Test]
    public function dayOfWeekSetsExplicitWeekdayListAndResetsHourAndMinute(): void
    {
        $expr = new ScheduleExpression()->dayOfWeek(1, 3);

        self::assertSame('0 0 * * 1,3', (string) $expr);
    }
}
