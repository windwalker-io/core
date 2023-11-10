<?php

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use NunoMaduro\Collision\Handler;
use NunoMaduro\Collision\Writer;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;
use Whoops\Run;
use Windwalker\Core\CliServer\Swoole\SwooleInspector;
use Windwalker\Filesystem\FileObject;
use Windwalker\Http\Helper\ResponseHelper;

/**
 * The CliServerRuntime class.
 */
class CliServerRuntime
{
    protected static SwooleInspector $inspector;

    protected static CliServerStateManager $cliServerStateManager;

    public static ConsoleOutput $output;

    protected static CliServerState $state;

    protected static \Closure $errorHandler;

    public static function boot(string $engine, array $argv): CliServerState
    {
        self::$state = self::parseArgv($argv);

        self::$inspector = new SwooleInspector(self::$state);

        return self::$state;
    }

    public static function parseArgv(array $argv): CliServerState
    {
        $stateFilePath = $argv[1] ?? '';

        $cliServerStateManager = new CliServerStateManager($stateFilePath);

        self::$cliServerStateManager = $cliServerStateManager;

        return $cliServerStateManager->getState();
    }

    public static function getServerState(): CliServerState
    {
        return self::$state ??= self::$cliServerStateManager->getState();
    }

    public static function saveServerState(?CliServerState $state = null): FileObject|false
    {
        if ($state) {
            static::$state = $state;
        }

        if (!self::$cliServerStateManager->getFilePath()) {
            return false;
        }

        return self::$cliServerStateManager->writeState(self::$state);
    }

    public static function registerErrorReporting(int $defaultErrorReporting = 0, int $devErrorReporting = -1): void
    {
        ini_set('display_errors', 'stderr');
        error_reporting(
            static::isDev() ? -1 : $defaultErrorReporting
        );
    }

    public static function log(string|iterable $message, bool $newline = false): void
    {
        self::getOutput()->write($message, $newline);
    }

    public static function logLine(string|iterable $message): void
    {
        self::getOutput()->writeln($message);
    }

    public static function logNewLine(int $lines): void
    {
        self::getOutput()->write(str_repeat("\n", $lines));
    }

    public static function getOutput(): ConsoleOutputInterface
    {
        if (!isset(self::$output)) {
            self::$output = new ConsoleOutput();
            self::$output->setVerbosity(static::getServerState()->getVerbosity());
        }

        return self::$output;
    }

    public static function getStyledOutput(): SymfonyStyle
    {
        return new SymfonyStyle(new ArrayInput([]), self::getOutput());
    }

    public static function getInspector(): SwooleInspector
    {
        return self::$inspector;
    }

    public static function createErrorHandler(): \Closure
    {
        if (!isset(static::$errorHandler)) {
            if (
                class_exists(Run::class)
                && class_exists(Handler::class)
                && static::isDev()
            ) {
                $run = new Run();
                $run->allowQuit(false);
                $run->pushHandler(
                    new Handler(
                        new Writer(output: static::getOutput())
                    )
                );

                static::$errorHandler = static function (\Throwable $e) use ($run) {
                    $run->handleException($e);
                };
            } else {
                static::$errorHandler = static function (\Throwable $e) {
                    static::renderThrowable($e, static::getOutput());
                };
            }
        }

        return static::$errorHandler;
    }

    public static function handleThrowable(\Throwable $e): void
    {
        if (ResponseHelper::isClientError($e->getCode())) {
            $output = self::getStyledOutput();
            $output->error("({$e->getCode()}) " . $e->getMessage());
            $output->writeln('  # File: ' . $e->getFile() . ':' . $e->getLine());
            $output->newLine(2);

            if (static::getOutput()->isVerbose()) {
                static::renderThrowable($e, static::getOutput());
            }

            return;
        }

        static::createErrorHandler()($e);
    }

    public static function isDev(): bool
    {
        return self::getAppEnv() !== 'prod';
    }

    public static function getAppEnv(): string
    {
        return env('APP_ENV') ?: 'dev';
    }

    public static function renderThrowable(\Throwable $e, OutputInterface $output): void
    {
        $terminal = new Terminal();

        do {
            $message = trim($e->getMessage());
            if ('' === $message || OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $class = get_debug_type($e);
                $title = sprintf('  [%s%s]  ', $class, 0 !== ($code = $e->getCode()) ? ' (' . $code . ')' : '');
                $len = Helper::width($title);
            } else {
                $len = 0;
            }

            if (str_contains($message, "@anonymous\0")) {
                $message = preg_replace_callback(
                    '/[a-zA-Z_\x7f-\xff][\\\\a-zA-Z0-9_\x7f-\xff]*+@anonymous\x00.*?\.php(?:0x?|:[0-9]++\$)[0-9a-fA-F]++/',
                    fn($m) => class_exists($m[0], false)
                        ? (get_parent_class($m[0]) ?: key(class_implements($m[0])) ?: 'class') . '@anonymous'
                        : $m[0],
                    $message
                );
            }

            $width = $terminal->getWidth() ? $terminal->getWidth() - 1 : \PHP_INT_MAX;
            $lines = [];
            foreach ('' !== $message ? preg_split('/\r?\n/', $message) : [] as $line) {
                foreach (static::splitStringByWidth($line, $width - 4) as $line) {
                    // pre-format lines to get the right string length
                    $lineLength = Helper::width($line) + 4;
                    $lines[] = [$line, $lineLength];

                    $len = max($lineLength, $len);
                }
            }

            $messages = [];
            if (!$e instanceof ExceptionInterface || OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $messages[] = sprintf(
                    '<comment>%s</comment>',
                    OutputFormatter::escape(
                        sprintf('In %s line %s:', basename($e->getFile()) ?: 'n/a', $e->getLine() ?: 'n/a')
                    )
                );
            }
            $messages[] = $emptyLine = sprintf('<error>%s</error>', str_repeat(' ', $len));
            if ('' === $message || OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $messages[] = sprintf(
                    '<error>%s%s</error>',
                    $title,
                    str_repeat(' ', max(0, $len - Helper::width($title)))
                );
            }
            foreach ($lines as $line) {
                $messages[] = sprintf(
                    '<error>  %s  %s</error>',
                    OutputFormatter::escape($line[0]),
                    str_repeat(' ', $len - $line[1])
                );
            }
            $messages[] = $emptyLine;
            $messages[] = '';

            $output->writeln($messages, OutputInterface::VERBOSITY_QUIET);

            if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $output->writeln('<comment>Exception trace:</comment>', OutputInterface::VERBOSITY_QUIET);

                // exception related properties
                $trace = $e->getTrace();

                array_unshift($trace, [
                    'function' => '',
                    'file' => $e->getFile() ?: 'n/a',
                    'line' => $e->getLine() ?: 'n/a',
                    'args' => [],
                ]);

                for ($i = 0, $count = \count($trace); $i < $count; ++$i) {
                    $class = $trace[$i]['class'] ?? '';
                    $type = $trace[$i]['type'] ?? '';
                    $function = $trace[$i]['function'] ?? '';
                    $file = $trace[$i]['file'] ?? 'n/a';
                    $line = $trace[$i]['line'] ?? 'n/a';

                    $output->writeln(
                        sprintf(
                            ' %s%s at <info>%s:%s</info>',
                            $class,
                            $function ? $type . $function . '()' : '',
                            $file,
                            $line
                        ),
                        OutputInterface::VERBOSITY_QUIET
                    );
                }

                $output->writeln('', OutputInterface::VERBOSITY_QUIET);
            }
        } while ($e = $e->getPrevious());
    }

    private static function splitStringByWidth(string $string, int $width): array
    {
        // str_split is not suitable for multi-byte characters, we should use preg_split to get char array properly.
        // additionally, array_slice() is not enough as some character has doubled width.
        // we need a function to split string not by character count but by string width
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return str_split($string, $width);
        }

        $utf8String = mb_convert_encoding($string, 'utf8', $encoding);
        $lines = [];
        $line = '';

        $offset = 0;
        while (preg_match('/.{1,10000}/u', $utf8String, $m, 0, $offset)) {
            $offset += \strlen($m[0]);

            foreach (preg_split('//u', $m[0]) as $char) {
                // test if $char could be appended to current line
                if (mb_strwidth($line . $char, 'utf8') <= $width) {
                    $line .= $char;
                    continue;
                }
                // if not, push current line to array and make new line
                $lines[] = str_pad($line, $width);
                $line = $char;
            }
        }

        $lines[] = \count($lines) ? str_pad($line, $width) : $line;

        mb_convert_variables($encoding, 'utf8', $lines);

        return $lines;
    }
}
