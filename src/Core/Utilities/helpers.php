<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace {
    if (!function_exists('env')) {
        /**
         * Get ENV var.
         *
         * @param string $name
         * @param mixed  $default
         *
         * @return  string
         *
         * @since  3.3
         */
        function env(string $name, $default = null): ?string
        {
            return $_SERVER[$name] ?? $_ENV[$name] ?? $default;
        }
    }

    if (!function_exists('__')) {
        /**
         * Get ENV var.
         *
         * @param string $name
         * @param mixed  $default
         *
         * @return  string
         *
         * @since  3.3
         */
        function __(string $name, $default = null): ?string
        {
            return $_SERVER[$name] ?? $_ENV[$name] ?? $default;
        }
    }
}

namespace Windwalker {

    use Windwalker\Core\Console\CmdWrapper;
    use Windwalker\Core\DateTime\Chronos;

    if (!function_exists('\Windwalker\include_files')) {
        function include_files(string $path, array $contextData = []): array
        {
            $resultBag = [];

            extract($contextData, EXTR_OVERWRITE);
            unset($contextData);

            foreach (glob($path) as $file) {
                $resultBag[] = include $file;
            }

            return array_merge(...$resultBag);
        }
    }

    if (!function_exists('\Windwalker\cmd')) {
        function cmd(string|\Closure $cmd, ?string $input = null, bool $ignoreError = false): CmdWrapper
        {
            return new CmdWrapper($cmd, $input, $ignoreError);
        }
    }

    if (!function_exists('\Windwalker\chronos')) {
        /**
         * chronos
         *
         * @param  mixed|string               $date
         * @param  string|\DateTimeZone|null  $tz
         *
         * @return  Chronos
         *
         * @throws \Exception
         */
        function chronos(mixed $date = 'now', string|\DateTimeZone $tz = null): Chronos
        {
            return new Chronos($date, $tz);
        }
    }

    if (!function_exists('\Windwalker\now')) {
        /**
         * chronos
         *
         * @param  string                     $format
         * @param  string|\DateTimeZone|null  $tz
         *
         * @return  Chronos
         *
         * @throws \Exception
         */
        function now(string $format, string|\DateTimeZone $tz = null): string
        {
            return Chronos::now($format, $tz);
        }
    }

    if (!function_exists('\Windwalker\with')) {
        /**
         * Nothing but just return self for some flow control use case.
         *
         * @param  mixed  $value
         *
         * @return  mixed
         */
        function with(mixed $value): mixed
        {
            return $value;
        }
    }
}
