<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace {

    use Windwalker\Core\View\CollapseWrapper;

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

    if (!function_exists('html_escape')) {
        /**
         * html_escape
         *
         * @param string $string
         * @param bool   $nl2br
         * @param bool   $doubleEncode
         *
         * @return  string
         *
         * @since  3.4
         */
        function html_escape(mixed $string, bool $nl2br = false, bool $doubleEncode = true): string
        {
            $string = htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8', $doubleEncode);

            if ($nl2br) {
                $string = nl2br($string);
            }

            return $string;
        }
    }
}

namespace Windwalker {

    use Windwalker\Core\Console\CmdWrapper;
    use Windwalker\Core\DateTime\Chronos;
    use Windwalker\Core\Http\CoreResponse;
    use Windwalker\Core\Runtime\Runtime;
    use Windwalker\Core\Service\FilterService;
    use Windwalker\Core\Utilities\Dumper;
    use Windwalker\Core\View\CollapseWrapper;

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
         * @return Chronos|null
         */
        function chronos(mixed $date = 'now', string|\DateTimeZone $tz = null): ?Chronos
        {
            return Chronos::wrapOrNull($date, $tz);
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
        function response(mixed $data = '', int $status = 200, array $headers = []): CoreResponse
        {
            return new CoreResponse($data, $status, $headers);
        }
    }

    if (!function_exists('\Windwalker\collapse')) {
        function collapse(...$args): CollapseWrapper
        {
            return new CollapseWrapper($args);
        }
    }
}
