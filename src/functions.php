<?php

declare(strict_types=1);

namespace {

    if (!function_exists('env')) {
        /**
         * Get ENV var.
         *
         * @param  string  $name
         * @param  mixed   $default
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
         * @param  string  $string
         * @param  bool    $nl2br
         * @param  bool    $doubleEncode
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

    use Closure;
    use DateTimeInterface;
    use DateTimeZone;
    use Exception;
    use Psr\Log\LogLevel;
    use Windwalker\Core\Console\CmdWrapper;
    use Windwalker\Core\DateTime\Chronos;
    use Windwalker\Core\DateTime\ChronosService;
    use Windwalker\Core\Http\CoreResponse;
    use Windwalker\Core\Manager\Logger;
    use Windwalker\Core\Runtime\ConfigLoader;
    use Windwalker\Core\Runtime\Runtime;
    use Windwalker\Core\Service\FilterService;
    use Windwalker\Core\Utilities\Dumper;
    use Windwalker\Core\View\CollapseWrapper;

    if (!function_exists('Windwalker\ds')) {
        /**
         * Dump to server.
         *
         * If server not running, will dump to HTML.
         *
         * @param  mixed  ...$args
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
            return ConfigLoader::includeArrays($path, $contextData);
        }
    }

    if (!function_exists('\Windwalker\cmd')) {
        function cmd(string|Closure $cmd, ?string $input = null, bool $ignoreError = false): CmdWrapper
        {
            return new CmdWrapper($cmd, $input, $ignoreError);
        }
    }

    if (!function_exists('\Windwalker\chronos')) {
        /**
         * chronos
         *
         * @param  mixed|string              $date
         * @param  string|DateTimeZone|null  $tz
         *
         * @return Chronos
         */
        function chronos(mixed $date = 'now', string|DateTimeZone|null $tz = null): Chronos
        {
            return Chronos::wrap($date, $tz);
        }
    }

    if (!function_exists('\Windwalker\chronosOrNull')) {
        /**
         * chronos
         *
         * @param  mixed|string              $date
         * @param  string|DateTimeZone|null  $tz
         *
         * @return Chronos|null
         * @deprecated Use try_chronos()
         */
        function chronosOrNull(mixed $date = 'now', string|DateTimeZone|null $tz = null): ?Chronos
        {
            return Chronos::wrapOrNull($date, $tz);
        }
    }

    if (!function_exists('\Windwalker\try_chronos')) {
        /**
         * try_chronos
         *
         * @param  mixed|string              $date
         * @param  string|DateTimeZone|null  $tz
         *
         * @return Chronos|null
         */
        function try_chronos(mixed $date = 'now', string|DateTimeZone|null $tz = null): ?Chronos
        {
            return Chronos::tryWrap($date, $tz);
        }
    }

    if (!function_exists('\Windwalker\now')) {
        /**
         * chronos
         *
         * @param  string                    $format
         * @param  string|DateTimeZone|null  $tz
         *
         * @return  Chronos
         *
         * @throws Exception
         */
        function now(string $format, string|DateTimeZone|null $tz = null): string
        {
            return Chronos::now($format, $tz);
        }
    }

    if (!function_exists('\Windwalker\with')) {
        /**
         * Nothing but just return self for some flow control use case.
         *
         * @template T
         *
         * @param  mixed|T  $value
         *
         * @return  mixed|T
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
            return CoreResponse::fromString($data, $status, $headers);
        }
    }

    if (!function_exists('\Windwalker\collapse')) {
        function collapse(...$args): CollapseWrapper
        {
            return new CollapseWrapper($args);
        }
    }

    if (!function_exists('\Windwalker\log')) {
        function log(string|array $channel, string|int $level, mixed $message, array $context = []): void
        {
            Logger::log($channel, $level, $message, $context);
        }
    }

    if (!function_exists('\Windwalker\log_info')) {
        function log_info(string|array $channel, mixed $message, array $context = []): void
        {
            Logger::log($channel, LogLevel::INFO, $message, $context);
        }
    }

    if (!function_exists('\Windwalker\log_debug')) {
        function log_debug(string|array $channel, mixed $message, array $context = []): void
        {
            Logger::log($channel, LogLevel::DEBUG, $message, $context);
        }
    }

    if (!function_exists('\Windwalker\log_notice')) {
        function log_notice(string|array $channel, mixed $message, array $context = []): void
        {
            Logger::log($channel, LogLevel::NOTICE, $message, $context);
        }
    }

    if (!function_exists('\Windwalker\date_compare')) {
        /**
         * Compare date with another date. All dates will be compared as UTC timezone.
         *
         * ```php
         * date_compare(
         *     'now', // This will be UTC timezone
         *     $date2, // Another date with timezone 'Asia/Tokyo'
         * );
         * ```
         *
         * If you want to compare UTC with local time, use this.
         *
         * ```php
         * date_compare(
         *     chronos('now', 'Asia/Tokyo'),
         *     $date2, // Another date with timezone 'Asia/Tokyo'
         * );
         * ```
         *
         * @param  string|DateTimeInterface  $date1
         * @param  string|DateTimeInterface  $date2
         * @param  string|null               $operator
         *
         * @return  bool|int
         *
         * @throws Exception
         */
        function date_compare(
            string|DateTimeInterface $date1,
            string|DateTimeInterface $date2,
            ?string $operator = null
        ): bool|int {
            return ChronosService::compare($date1, $date2, $operator);
        }
    }
}
