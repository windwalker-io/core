<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Seed;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Events\Console\MessageOutputEvent;
use Windwalker\Core\Migration\AbstractMigration;
use Windwalker\Core\Seed\CountingOutputTrait;

#[CoversClass(CountingOutputTrait::class)]
class CountingOutputTraitTest extends TestCase
{
    #[Test]
    public function printCountingIncrementsCountAndEmitsFormattedMessage(): void
    {
        $instance = new class {
            use CountingOutputTrait;
        };

        /** @var MessageOutputEvent[] $events */
        $events = [];
        $instance->onMessages(static function (MessageOutputEvent $event) use (&$events) {
            $events[] = $event;
        });

        $instance->printCounting(1, 'Hello');

        self::assertSame(1, $instance->count);
        self::assertCount(1, $events);
        self::assertTrue(str_starts_with($events[0]->messages, "\r"));
        self::assertStringContainsString('(1)', $events[0]->messages);
        self::assertStringEndsWith('Hello', $events[0]->messages);
        self::assertFalse($events[0]->newLine);
    }

    #[Test]
    public function printCountingAccumulatesCountAcrossCalls(): void
    {
        $instance = new class {
            use CountingOutputTrait;
        };

        $instance->printCounting(2);
        $instance->printCounting(3);

        self::assertSame(5, $instance->count);
    }

    #[Test]
    public function printCountingEmitsLeadingBlankLineOnlyOnFirstCallForMigrations(): void
    {
        $instance = new class extends AbstractMigration {};

        $events = [];
        $instance->onMessages(static function (MessageOutputEvent $event) use (&$events) {
            $events[] = $event;
        });

        $instance->printCounting(1, 'first');
        $instance->printCounting(1, 'second');

        self::assertCount(3, $events);
        self::assertSame('', $events[0]->messages);
        self::assertStringContainsString('first', $events[1]->messages);
        self::assertStringContainsString('second', $events[2]->messages);
    }

    #[Test]
    public function printCountingDoesNotEmitLeadingBlankLineForNonMigrationClasses(): void
    {
        $instance = new class {
            use CountingOutputTrait;
        };

        $events = [];
        $instance->onMessages(static function (MessageOutputEvent $event) use (&$events) {
            $events[] = $event;
        });

        $instance->printCounting(1, 'only');

        self::assertCount(1, $events);
        self::assertStringContainsString('only', $events[0]->messages);
    }
}
