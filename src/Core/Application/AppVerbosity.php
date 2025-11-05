<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Psr\Log\LogLevel;
use Windwalker\Utilities\Exception\VerbosityExceptionInterface;

enum AppVerbosity: int
{
    /**
     * All error messages will be hidden. Only return fallback messages.
     */
    case HIDDEN = 0;
    /**
     * Show original error messages without details and backtraces.
     */
    case NORMAL = 1;
    /**
     * Show full error messages with details and backtraces, SQL etc.
     */
    case VERBOSE = 2;
    /**
     * Show more verbose information, like some debug information.
     */
    case VERY_VERBOSE = 3;

    public function isHidden(): bool
    {
        return $this->value === self::HIDDEN->value;
    }

    public function isNormal(): bool
    {
        return $this->value === self::NORMAL->value;
    }

    public function isVerbose(): bool
    {
        return $this->value >= self::VERBOSE->value;
    }

    public function isVeryVerbose(): bool
    {
        return $this->value >= self::VERY_VERBOSE->value;
    }

    public function displayMessage(\Throwable|string $e, string $fallback = 'Something went wrong'): string
    {
        if ($e instanceof \Throwable) {
            if ($e instanceof VerbosityExceptionInterface) {
                $e = $e->getMessageByVerbosity($this->value);
            } else {
                $e = $e->getMessage();
            }
        }

        if ($this === self::HIDDEN) {
            return $fallback;
        }

        if ($this->value >= self::NORMAL->value) {
            return $e;
        }

        return $fallback;
    }

    public function debugMessage(\Throwable|string $e): string
    {
        if ($e instanceof VerbosityExceptionInterface) {
            return $e->getMessageByVerbosity($this->value);
        }

        return $e->getMessage();
    }

    public function toLogLevel(): string
    {
        // Todo: After 5.0, should log info at VERBOSE
        return match ($this) {
            self::HIDDEN => LogLevel::ERROR,
            self::NORMAL => LogLevel::INFO,
            self::VERBOSE, self::VERY_VERBOSE => LogLevel::DEBUG,
        };
    }
}
