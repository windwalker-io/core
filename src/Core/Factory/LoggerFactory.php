<?php

declare(strict_types=1);

namespace Windwalker\Core\Factory;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Windwalker\Core\Application\AppVerbosity;
use Windwalker\Core\DI\ServiceFactoryInterface;
use Windwalker\Core\DI\ServiceFactoryTrait;
use Windwalker\Core\Runtime\Runtime;
use Windwalker\Core\Service\LoggerService;
use Windwalker\DI\Attributes\Factory;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\DI\Container;

#[Isolation]
class LoggerFactory implements ServiceFactoryInterface
{
    use ServiceFactoryTrait;

    public function getConfigPrefix(): string
    {
        return 'logs';
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultFactory(string $name, ...$args): mixed
    {
        return $this->config->getDeep($this->getFactoryPath($this->getDefaultName()));
    }

    public static function rotatingFileHandler(
        ?string $filename = null,
        ?int $maxFiles = null,
        int|string|null $level = null,
        ...$args
    ): \Closure {
        return #[Factory]
        static function (Container $container, string $tag) use ($level, $filename, $maxFiles, $args) {
            $level ??= Runtime::$verbosity->toLogLevel();

            $filename ??= $container->getParam('@logs') . '/' . $tag . '.log';
            $maxFiles = (int) ($container->getParam('logs.max_files') ?? 7);

            return new RotatingFileHandler(
                $filename,
                $maxFiles,
                $level,
                ...$args
            );
        };
    }

    public static function monolog(
        ?string $name = null,
        array $handlers = [],
        array $processors = [],
        string $formatter = 'line_formatter'
    ): \Closure {
        return #[Factory]
        static function (Container $container) use ($formatter, $processors, $handlers, $name) {
            $logger = new Logger(
                $name,
                array_map(
                    static function ($handler) use ($formatter, $container, $name) {
                        /** @var HandlerInterface $handler */
                        $handler = $container->resolve($handler, tag: $name);

                        return $handler->setFormatter(
                            $container->resolve(
                                $container->getParam('logs.factories.formatters.' . $formatter)
                            )
                        );
                    },
                    $handlers
                ),
                array_map(fn($processor) => $container->resolve($processor, ['_name' => $name]), $processors)
            );

            foreach ($container->getParam('logs.global_handlers') as $handler) {
                $logger->pushHandler($container->resolve($handler));
            }

            foreach ($container->getParam('logs.global_processors') as $processor) {
                $logger->pushProcessor($container->resolve($processor));
            }

            return $logger;
        };
    }

    public static function lineFormatter(): \Closure
    {
        return #[Factory] function () {
            return new LineFormatter(
                allowInlineLineBreaks: true
            )->includeStacktraces(true, LoggerService::parseTrace(...));
        };
    }
}
