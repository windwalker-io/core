<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;
use Windwalker\Core\Application\AppVerbosity;
use Windwalker\Utilities\Exception\VerbosityExceptionInterface;

class AppVerbosityTest extends TestCase
{
    #[DataProvider('levelProvider')]
    #[Test]
    public function levelChecks(
        AppVerbosity $v,
        bool $hidden,
        bool $normal,
        bool $exactNormal,
        bool $verbose,
        bool $exactVerbose,
        bool $veryVerbose,
        bool $exactVeryVerbose,
    ): void {
        self::assertSame($hidden, $v->isHidden());
        self::assertSame($normal, $v->isNormal());
        self::assertSame($exactNormal, $v->isExactlyNormal());
        self::assertSame($verbose, $v->isVerbose());
        self::assertSame($exactVerbose, $v->isExactlyVerbose());
        self::assertSame($veryVerbose, $v->isVeryVerbose());
        self::assertSame($exactVeryVerbose, $v->isExactlyVeryVerbose());
    }

    public static function levelProvider(): array
    {
        return [
            'hidden' => [AppVerbosity::HIDDEN, true, false, false, false, false, false, false],
            'normal' => [AppVerbosity::NORMAL, false, true, true, false, false, false, false],
            'verbose' => [AppVerbosity::VERBOSE, false, true, false, true, true, false, false],
            'very verbose' => [AppVerbosity::VERY_VERBOSE, false, true, false, true, false, true, true],
        ];
    }

    #[Test]
    public function displayMessage(): void
    {
        self::assertSame('fallback', AppVerbosity::HIDDEN->displayMessage('msg', 'fallback'));
        self::assertSame('Something went wrong', AppVerbosity::HIDDEN->displayMessage('msg'));
        self::assertSame('msg', AppVerbosity::NORMAL->displayMessage('msg'));
        self::assertSame('boom', AppVerbosity::NORMAL->displayMessage(new RuntimeException('boom')));
        self::assertSame('fb', AppVerbosity::HIDDEN->displayMessage(new RuntimeException('boom'), 'fb'));
    }

    #[Test]
    public function displayMessageUsesVerbosityException(): void
    {
        $e = new class ('plain') extends RuntimeException implements VerbosityExceptionInterface {
            public function getDebugMessage(): string
            {
                return 'debug';
            }

            public function getMessageByVerbosity(int|bool $verbosity = false): string
            {
                return "level-$verbosity";
            }
        };

        self::assertSame('plain', AppVerbosity::NORMAL->displayMessage($e));
        self::assertSame('level-2', AppVerbosity::VERBOSE->displayMessage($e));
        self::assertSame('level-3', AppVerbosity::VERY_VERBOSE->displayMessage($e));
    }

    #[Test]
    public function debugMessage(): void
    {
        $e = new class ('plain') extends RuntimeException implements VerbosityExceptionInterface {
            public function getDebugMessage(): string
            {
                return 'debug';
            }

            public function getMessageByVerbosity(int|bool $verbosity = false): string
            {
                return "level-$verbosity";
            }
        };

        self::assertSame('level-1', AppVerbosity::NORMAL->debugMessage($e));
        self::assertSame('boom', AppVerbosity::NORMAL->debugMessage(new RuntimeException('boom')));
    }

    #[Test]
    public function toLogLevel(): void
    {
        self::assertSame(LogLevel::ERROR, AppVerbosity::HIDDEN->toLogLevel());
        self::assertSame(LogLevel::INFO, AppVerbosity::NORMAL->toLogLevel());
        self::assertSame(LogLevel::DEBUG, AppVerbosity::VERBOSE->toLogLevel());
        self::assertSame(LogLevel::DEBUG, AppVerbosity::VERY_VERBOSE->toLogLevel());
    }
}
