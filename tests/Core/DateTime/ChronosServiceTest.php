<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use Windwalker\Core\DateTime\Instant;
use Windwalker\Core\DateTime\PlainDate;
use Windwalker\Core\DateTime\PlainTime;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\Clock;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Runtime\Config;
use Windwalker\Database\DatabaseAdapter;

#[CoversClass(ChronosService::class)]
class ChronosServiceTest extends TestCase
{
    private ChronosService $instance;

    #[Before]
    protected function prepareInstance(): void
    {
        $this->instance = new ChronosService(
            new Config(
                [
                    'app' => [
                        'timezone' => 'Asia/Taipei',
                        'server_timezone' => 'UTC',
                    ],
                ]
            )
        );
    }

    #[After]
    protected function resetClock(): void
    {
        Clock::reset();
    }

    private function injectLang(ChronosService $service): LangService
    {
        $lang = $this->createStub(LangService::class);
        $lang->method('trans')->willReturnCallback(static fn(string $id, ...$args) => $id);
        $lang->method('choice')->willReturnCallback(
            static fn(string $id, int|float $number, ...$args) => $id . ':' . $number
        );

        $ref = new ReflectionProperty(ChronosService::class, 'lang');
        $ref->setValue($service, $lang);

        return $lang;
    }

    #[Test]
    public function getTimezoneReadsFromConfig(): void
    {
        self::assertSame('Asia/Taipei', $this->instance->getTimezone());
    }

    #[Test]
    public function getServerTimezoneReadsFromConfig(): void
    {
        self::assertSame('UTC', $this->instance->getServerTimezone());
    }

    #[Test]
    public function getSqlFormatDelegatesToInjectedDatabaseAdapter(): void
    {
        $db = $this->createStub(DatabaseAdapter::class);
        $db->method('getDateFormat')->willReturn('Y-m-d H:i:s');

        $service = new ChronosService(new Config(), $db);

        self::assertSame('Y-m-d H:i:s', $service->getSqlFormat());
    }

    #[Test]
    public function getSqlFormatAcceptsExplicitDatabaseAdapterOverride(): void
    {
        $default = $this->createStub(DatabaseAdapter::class);
        $default->method('getDateFormat')->willReturn('default-format');

        $override = $this->createStub(DatabaseAdapter::class);
        $override->method('getDateFormat')->willReturn('override-format');

        $service = new ChronosService(new Config(), $default);

        self::assertSame('override-format', $service->getSqlFormat($override));
    }

    #[Test]
    public function getNullDateDelegatesToInjectedDatabaseAdapter(): void
    {
        $db = $this->createStub(DatabaseAdapter::class);
        $db->method('getNullDate')->willReturn('0000-00-00 00:00:00');

        $service = new ChronosService(new Config(), $db);

        self::assertSame('0000-00-00 00:00:00', $service->getNullDate());
    }

    #[Test]
    public function isNullDateDelegatesToInjectedDatabaseAdapter(): void
    {
        $db = $this->createStub(DatabaseAdapter::class);
        $db->method('isNullDate')->willReturn(true);

        $service = new ChronosService(new Config(), $db);

        self::assertTrue($service->isNullDate('0000-00-00 00:00:00'));
    }

    #[Test]
    public function toServerReturnsNullForNullOrEmptyString(): void
    {
        self::assertNull($this->instance->toServer(null));
        self::assertNull($this->instance->toServer(''));
    }

    #[Test]
    public function toServerConvertsLocalTimeToServerTimezone(): void
    {
        $result = $this->instance->toServer('2020-01-01 10:00:00');

        self::assertSame('2020-01-01 02:00:00', $result->format('Y-m-d H:i:s'));
        self::assertSame('UTC', $result->getTimezone()->getName());
    }

    #[Test]
    public function toServerFormatReturnsEmptyStringForNullOrEmptyInput(): void
    {
        self::assertSame('', $this->instance->toServerFormat(null));
        self::assertSame('', $this->instance->toServerFormat(''));
    }

    #[Test]
    public function toServerFormatFormatsConvertedDate(): void
    {
        self::assertSame(
            '2020-01-01 02:00:00',
            $this->instance->toServerFormat('2020-01-01 10:00:00')
        );
    }

    #[Test]
    public function toLocalReturnsNullForNullOrEmptyString(): void
    {
        self::assertNull($this->instance->toLocal(null));
        self::assertNull($this->instance->toLocal(''));
    }

    #[Test]
    public function toLocalConvertsServerTimeToLocalTimezone(): void
    {
        $result = $this->instance->toLocal('2020-01-01 02:00:00');

        self::assertSame('2020-01-01 10:00:00', $result->format('Y-m-d H:i:s'));
        self::assertSame('Asia/Taipei', $result->getTimezone()->getName());
    }

    #[Test]
    public function toLocalFormatReturnsEmptyStringForNullOrEmptyInput(): void
    {
        self::assertSame('', $this->instance->toLocalFormat(null));
        self::assertSame('', $this->instance->toLocalFormat(''));
    }

    #[Test]
    public function toLocalFormatFormatsConvertedDate(): void
    {
        self::assertSame(
            '2020-01-01 10:00:00',
            $this->instance->toLocalFormat('2020-01-01 02:00:00')
        );
    }

    #[Test]
    public function convertReturnsSameInstantWhenTimezonesAreIdentical(): void
    {
        $result = ChronosService::convert('2020-01-01 10:00:00', 'UTC', 'UTC');

        self::assertSame('2020-01-01 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function convertWrapsStringWithOffsetWithoutApplyingFromTimezone(): void
    {
        // $from is ignored because the string already carries its own UTC offset.
        $result = ChronosService::convert('2020-01-01T10:00:00+08:00', 'Asia/Tokyo', 'UTC');

        self::assertSame('2020-01-01 02:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function convertAppliesFromTimezoneWhenNoOffsetInString(): void
    {
        $result = ChronosService::convert('2020-01-01 10:00:00', 'Asia/Taipei', 'UTC');

        self::assertSame('2020-01-01 02:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function compareReturnsSpaceshipResultWhenNoOperatorGiven(): void
    {
        self::assertSame(-1, ChronosService::compare('2020-01-01', '2020-01-02'));
        self::assertSame(0, ChronosService::compare('2020-01-01', '2020-01-01'));
        self::assertSame(1, ChronosService::compare('2020-01-02', '2020-01-01'));
    }

    #[Test]
    public function compareSupportsEqualityOperators(): void
    {
        self::assertTrue(ChronosService::compare('2020-01-01', '2020-01-01', '='));
        self::assertFalse(ChronosService::compare('2020-01-01', '2020-01-02', '='));
        self::assertTrue(ChronosService::compare('2020-01-01', '2020-01-02', '!='));
        self::assertFalse(ChronosService::compare('2020-01-01', '2020-01-01', '!='));
    }

    #[Test]
    public function compareSupportsGreaterThanOperators(): void
    {
        self::assertTrue(ChronosService::compare('2020-01-02', '2020-01-01', '>'));
        self::assertTrue(ChronosService::compare('2020-01-02', '2020-01-01', 'gt'));
        self::assertTrue(ChronosService::compare('2020-01-01', '2020-01-01', '>='));
        self::assertTrue(ChronosService::compare('2020-01-01', '2020-01-01', 'gte'));
    }

    #[Test]
    public function compareSupportsLessThanOperators(): void
    {
        self::assertTrue(ChronosService::compare('2020-01-01', '2020-01-02', '<'));
        self::assertTrue(ChronosService::compare('2020-01-01', '2020-01-02', 'lt'));
        self::assertTrue(ChronosService::compare('2020-01-01', '2020-01-01', '<='));
        self::assertTrue(ChronosService::compare('2020-01-01', '2020-01-01', 'lte'));
    }

    #[Test]
    public function compareThrowsForInvalidOperator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid operator: bad-op/');

        ChronosService::compare('2020-01-01', '2020-01-02', 'bad-op');
    }

    #[Test]
    public function createLocalUsesConfiguredLocalTimezoneAsSource(): void
    {
        $result = $this->instance->createLocal('2020-01-01 10:00:00');

        self::assertSame('Asia/Taipei', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function createAsLocalConvertsFromGivenTimezoneToLocal(): void
    {
        $result = $this->instance->createAsLocal('2020-01-01 10:00:00', 'UTC');

        self::assertSame('Asia/Taipei', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 18:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function createAsConvertsBetweenArbitraryTimezones(): void
    {
        $result = $this->instance->createAs('2020-01-01 10:00:00', 'UTC', 'Asia/Tokyo');

        self::assertSame('Asia/Tokyo', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 19:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function createServerAsLocalConvertsFromServerTimezoneToLocal(): void
    {
        $result = $this->instance->createServerAsLocal('2020-01-01 02:00:00');

        self::assertSame('Asia/Taipei', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 10:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function createServerAsConvertsFromServerTimezoneToGivenTimezone(): void
    {
        $result = $this->instance->createServerAs('2020-01-01 02:00:00', 'Asia/Tokyo');

        self::assertSame('Asia/Tokyo', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 11:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function createLocalAsServerConvertsFromLocalTimezoneToServer(): void
    {
        $result = $this->instance->createLocalAsServer('2020-01-01 10:00:00');

        self::assertSame('UTC', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 02:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function createLocalAsConvertsFromLocalTimezoneToGivenTimezone(): void
    {
        $result = $this->instance->createLocalAs('2020-01-01 10:00:00', 'Asia/Tokyo');

        self::assertSame('Asia/Tokyo', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 11:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function createLocalAsFallsBackToServerTimezoneWhenNoTargetGiven(): void
    {
        $result = $this->instance->createLocalAs('2020-01-01 10:00:00');

        self::assertSame('UTC', $result->getTimezone()->getName());
        self::assertSame('2020-01-01 02:00:00', $result->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function relativeReturnsLessThanMinuteMessage(): void
    {
        $this->injectLang($this->instance);

        $result = $this->instance->relative('2020-01-01 10:00:30', current: '2020-01-01 10:00:50');

        self::assertSame('windwalker.datetime.relative.less.than.minute', $result);
    }

    #[Test]
    public function relativeReturnsMinutesMessage(): void
    {
        $this->injectLang($this->instance);

        $result = $this->instance->relative('2020-01-01 10:00:00', current: '2020-01-01 10:05:00');

        self::assertSame('windwalker.datetime.relative.minute:5', $result);
    }

    #[Test]
    public function relativeReturnsHoursMessage(): void
    {
        $this->injectLang($this->instance);

        $result = $this->instance->relative('2020-01-01 10:00:00', current: '2020-01-01 13:00:00');

        self::assertSame('windwalker.datetime.relative.hour:3', $result);
    }

    #[Test]
    public function relativeReturnsDaysMessage(): void
    {
        $this->injectLang($this->instance);

        $result = $this->instance->relative('2020-01-01 10:00:00', current: '2020-01-03 10:00:00');

        self::assertSame('windwalker.datetime.relative.day:2', $result);
    }

    #[Test]
    public function relativeReturnsWeeksMessage(): void
    {
        $this->injectLang($this->instance);

        $result = $this->instance->relative('2020-01-01 10:00:00', current: '2020-01-15 10:00:00');

        self::assertSame('windwalker.datetime.relative.week:2', $result);
    }

    #[Test]
    public function relativeForcesSpecificUnitWhenRequested(): void
    {
        $this->injectLang($this->instance);

        // 30 hours apart would naturally fall into the "day" branch, but forcing
        // UNIT_HOUR keeps it reporting in hours instead.
        $result = $this->instance->relative(
            '2020-01-01 00:00:00',
            ChronosService::UNIT_HOUR,
            '2020-01-02 06:00:00'
        );

        self::assertSame('windwalker.datetime.relative.hour:30', $result);
    }

    #[Test]
    public function relativeFallsBackToFormatForOldDates(): void
    {
        $this->injectLang($this->instance);

        $result = $this->instance->relative('2020-01-01 10:00:00', current: '2020-03-01 10:00:00');

        self::assertSame(Chronos::wrap('2020-01-01 10:00:00')->format(Chronos::FORMAT_YMD_HIS), $result);
    }

    #[Test]
    public function relativeUsesCustomFormatForOldDates(): void
    {
        $this->injectLang($this->instance);

        $result = $this->instance->relative(
            '2020-01-01 10:00:00',
            current: '2020-03-01 10:00:00',
            format: 'Y/m/d'
        );

        self::assertSame('2020/01/01', $result);
    }

    #[Test]
    public function setClockDelegatesToClock(): void
    {
        ChronosService::setClock('2021-09-10 11:12:13');

        self::assertSame('2021-09-10 11:12:13', Clock::now()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function compareSupportsValueObjects(): void
    {
        self::assertSame(-1, ChronosService::compare(PlainDate::of(2020, 1, 1), PlainDate::of(2020, 1, 2)));
        self::assertTrue(ChronosService::compare(PlainDate::of(2020, 1, 2), '2020-01-01', '>'));
        self::assertTrue(ChronosService::compare('10:00', PlainTime::of(10), '='));
        self::assertSame(1, ChronosService::compare(Instant::fromEpochSeconds(10), new \DateTimeImmutable('@5')));
        self::assertSame(-1, ChronosService::compare(new \DateTimeImmutable('@5'), Instant::fromEpochSeconds(10)));
        self::assertTrue(ChronosService::compare(Instant::fromEpochSeconds(0), '1970-01-01T00:00:00Z', '='));

        $this->expectException(\TypeError::class);
        ChronosService::compare(PlainDate::of(2020, 1, 1), PlainTime::of(1));
    }
}
