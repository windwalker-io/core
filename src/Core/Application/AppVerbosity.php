<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

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

    public function isVerbose(): bool
    {
        return $this->value >= self::VERBOSE->value;
    }

    public function message(\Throwable|string $e, string $fallback = 'Something went wrong'): string
    {
        if ($e instanceof \Throwable) {
            $e = $e->getMessage();
        }

        if ($this === self::HIDDEN) {
            return $fallback;
        }

        if ($this->value >= self::NORMAL->value) {
            return $e;
        }

        return $fallback;
    }
}
