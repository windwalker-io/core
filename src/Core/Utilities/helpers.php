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
    use Windwalker\Core\Http\ResponseFactory;
    use Windwalker\Core\Runtime\Runtime;
    use Windwalker\Core\Service\FilterService;
    use Windwalker\Core\Utilities\Dumper;

    if (!function_exists('ds')) {
        /**
         * Dump to server.
         *
         * If server not running, will dump to HTML.
         *
         * @param mixed ...$args
         *
         * @return  void
         *
         * @since  3.4.6
         */
        function ds(...$args)
        {
            $dumper = new Dumper();

            $dumper->dump(...$args);
        }
    }

    if (!function_exists('\Windwalker\include_arrays')) {
        function include_arrays(string $path, array $contextData = []): array
        {
            $resultBag = [];

            extract($contextData, EXTR_OVERWRITE);
            unset($contextData);

            foreach (glob($path) as $file) {
                $resultBag += include $file;
            }

            return $resultBag;
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

    if (!function_exists('\Windwalker\filter')) {
        /**
         * Nothing but just return self for some flow control use case.
         *
         * @param  mixed         $value
         * @param  string|array  $command
         *
         * @return  mixed
         */
        function filter(mixed $value, string|array $command): mixed
        {
            return Runtime::getContainer()->get(FilterService::class)->filter($value, $command);
        }
    }

    if (!function_exists('\Windwalker\validate')) {
        /**
         * Nothing but just return self for some flow control use case.
         *
         * @param  mixed         $value
         * @param  string|array  $command
         *
         * @return  bool
         */
        function validate(mixed $value, string|array $command): bool
        {
            return Runtime::getContainer()->get(FilterService::class)->validate($value, $command);
        }
    }

    if (!function_exists('\Windwalker\response')) {
        function response(mixed $data = '', int $status = 200, array $headers = []): ResponseFactory
        {
            return new ResponseFactory($data, $status, $headers);
        }
    }
}
