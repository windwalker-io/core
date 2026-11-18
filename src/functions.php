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
    use JetBrains\PhpStorm\Deprecated;
    use Psr\Log\LogLevel;
    use Windwalker\Core\Console\CmdWrapper;
    use Windwalker\Core\Http\CoreResponse;
    use Windwalker\Core\Manager\Logger;
    use Windwalker\Core\Runtime\ConfigLoader;
    use Windwalker\Core\Runtime\Runtime;
    use Windwalker\Core\Service\FilterService;
    use Windwalker\Core\Utilities\Dumper;
    use Windwalker\Core\View\CollapseWrapper;
    use Windwalker\Filter\FilterInterface;
    use Windwalker\Filter\ValidatorInterface;

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

    if (!function_exists('\Windwalker\with')) {
        /**
         * Nothing but just return self for some flow control use case.
         *
         * @template T
         *
         * @param  mixed|T  $value
         *
         * @return  mixed|T
         *
         * @deprecated  No longer needed.
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
         *
         * @deprecated  Use filterWith() primarily.
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
         *
         * @deprecated  Use validateWith() primarily.
         */
        function validate(mixed $value, string|array $command): bool
        {
            return Runtime::getContainer()->get(FilterService::class)->validate($value, $command);
        }
    }

    if (!function_exists('\Windwalker\filterWith')) {
        /**
         * Filter a value with callback rules. See FilterService::filterWith().
         *
         * @param  mixed                           $value
         * @param  \Closure|FilterInterface|array  $rule
         *
         * @return  mixed
         */
        function filterWith(mixed $value, \Closure|FilterInterface|array $rule): mixed
        {
            return Runtime::getContainer()->get(FilterService::class)->filterWith($value, $rule);
        }
    }

    if (!function_exists('\Windwalker\validateWith')) {
        /**
         * Validate a value with callback rules. See FilterService::validateWith().
         *
         * @param  mixed                              $value
         * @param  \Closure|ValidatorInterface|array  $rule
         *
         * @return  bool
         */
        function validateWith(mixed $value, \Closure|ValidatorInterface|array $rule): bool
        {
            return Runtime::getContainer()->get(FilterService::class)->validateWith($value, $rule);
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
}
