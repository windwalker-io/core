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
         * @return  ?string
         *
         * @since  3.3
         */
        function env(string $name, mixed $default = null): ?string
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
    use JetBrains\PhpStorm\Deprecated;
    use Psr\Log\LogLevel;
    use Windwalker\Core\Console\CmdWrapper;
    use Windwalker\Core\DateTime\Chronos;
    use Windwalker\Core\DateTime\ChronosService;
    use Windwalker\Core\DateTime\Contract\HasDateInterface;
    use Windwalker\Core\DateTime\Contract\HasInstantInterface;
    use Windwalker\Core\DateTime\Contract\HasTimeInterface;
    use Windwalker\Core\DateTime\Instant;
    use Windwalker\Core\DateTime\PlainDate;
    use Windwalker\Core\DateTime\PlainDateTime;
    use Windwalker\Core\DateTime\PlainTime;
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
        #[Deprecated('This is a debug function, remember to remove it before production.')]
        function ds(...$args)
        {
            $dumper = new Dumper();

            $dumper->dumpToServer(...$args);
        }
    }

    if (!function_exists('\Windwalker\include_arrays')) {
        function include_arrays(string $path, array $contextData = []): array
        {
            return ConfigLoader::includeArrays($path, $contextData);
        }
    }

    if (!function_exists('\Windwalker\include_glob')) {
        function include_glob(string $path, array $contextData = [], bool $merge = false): array
        {
            $files = \Windwalker\glob($path);

            extract($contextData, EXTR_OVERWRITE);
            unset($contextData);

            $results = [];

            foreach ($files as $file) {
                $results[] = include $file;
            }

            if ($merge) {
                $results = array_merge(...$results);
            }

            return $results;
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
         * @return  Chronos
         */
        function chronos(mixed $date = null, string|DateTimeZone|null $tz = null): Chronos
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
         * @return  Chronos|null
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
         * @return  Chronos|null
         */
        function try_chronos(mixed $date = 'now', string|DateTimeZone|null $tz = null): ?Chronos
        {
            return Chronos::tryWrap($date, $tz);
        }
    }

    if (!function_exists('\Windwalker\to_instant')) {
        /**
         * Strictly convert a value to Instant.
         *
         * Accepts: Instant (returned as is), any HasInstantInterface (Chronos), \DateTimeInterface,
         * and strict ISO 8601 string with time zone. PlainDate/PlainTime/PlainDateTime are rejected
         * because they have no time zone.
         *
         * @throws  \TypeError|\InvalidArgumentException
         */
        function to_instant(mixed $value): Instant
        {
            return match (true) {
                $value instanceof Instant => $value,
                $value instanceof HasInstantInterface => $value->toInstant(),
                $value instanceof DateTimeInterface => Instant::from($value),
                is_string($value) => Instant::parse($value),
                default => throw new \TypeError(sprintf('Cannot convert %s to Instant.', get_debug_type($value))),
            };
        }
    }

    if (!function_exists('\Windwalker\try_instant')) {
        /**
         * Same as to_instant() but returns NULL for null or empty string.
         */
        function try_instant(mixed $value): ?Instant
        {
            return $value === null || $value === '' ? null : to_instant($value);
        }
    }

    if (!function_exists('\Windwalker\to_plain_date')) {
        /**
         * Strictly convert a value to PlainDate, using the wall-clock date without any time zone conversion.
         *
         * Accepts: PlainDate (returned as is), any HasDateInterface (PlainDateTime, Chronos),
         * \DateTimeInterface, and strict `Y-m-d` string. Instant is rejected.
         *
         * @throws  \TypeError|\InvalidArgumentException
         */
        function to_plain_date(mixed $value): PlainDate
        {
            return match (true) {
                $value instanceof PlainDate => $value,
                $value instanceof HasDateInterface => $value->toPlainDate(),
                $value instanceof DateTimeInterface => PlainDate::from($value),
                is_string($value) => PlainDate::parse($value),
                default => throw new \TypeError(sprintf('Cannot convert %s to PlainDate.', get_debug_type($value))),
            };
        }
    }

    if (!function_exists('\Windwalker\try_plain_date')) {
        function try_plain_date(mixed $value): ?PlainDate
        {
            return $value === null || $value === '' ? null : to_plain_date($value);
        }
    }

    if (!function_exists('\Windwalker\to_plain_time')) {
        /**
         * Strictly convert a value to PlainTime, using the wall-clock time without any time zone conversion.
         *
         * Accepts: PlainTime (returned as is), any HasTimeInterface (PlainDateTime, Chronos),
         * \DateTimeInterface, and strict time string. Instant is rejected.
         *
         * @throws  \TypeError|\InvalidArgumentException
         */
        function to_plain_time(mixed $value): PlainTime
        {
            return match (true) {
                $value instanceof PlainTime => $value,
                $value instanceof HasTimeInterface => $value->toPlainTime(),
                $value instanceof DateTimeInterface => PlainTime::from($value),
                is_string($value) => PlainTime::parse($value),
                default => throw new \TypeError(sprintf('Cannot convert %s to PlainTime.', get_debug_type($value))),
            };
        }
    }

    if (!function_exists('\Windwalker\try_plain_time')) {
        function try_plain_time(mixed $value): ?PlainTime
        {
            return $value === null || $value === '' ? null : to_plain_time($value);
        }
    }

    if (!function_exists('\Windwalker\to_plain_datetime')) {
        /**
         * Strictly convert a value to PlainDateTime, using the wall-clock fields without any time zone conversion.
         *
         * Accepts: PlainDateTime (returned as is), Chronos, \DateTimeInterface, and strict ISO string
         * without time zone. PlainDate/PlainTime/Instant are rejected, combine or convert explicitly.
         *
         * @throws  \TypeError|\InvalidArgumentException
         */
        function to_plain_datetime(mixed $value): PlainDateTime
        {
            return match (true) {
                $value instanceof PlainDateTime => $value,
                $value instanceof Chronos => $value->toPlainDateTime(),
                $value instanceof DateTimeInterface => PlainDateTime::from($value),
                is_string($value) => PlainDateTime::parse($value),
                default => throw new \TypeError(
                    sprintf('Cannot convert %s to PlainDateTime.', get_debug_type($value))
                ),
            };
        }
    }

    if (!function_exists('\Windwalker\try_plain_datetime')) {
        function try_plain_datetime(mixed $value): ?PlainDateTime
        {
            return $value === null || $value === '' ? null : to_plain_datetime($value);
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
         * @throws  Exception
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
            if (is_string($data)) {
                return CoreResponse::fromString($data, $status, $headers);
            }

            return new CoreResponse($data, $status, $headers);
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
         * @throws  Exception
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
