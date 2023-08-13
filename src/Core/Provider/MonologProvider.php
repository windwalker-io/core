<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Windwalker\Core\Runtime\Runtime;
use Windwalker\DI\Container;
use Windwalker\DI\Definition\ObjectBuilderDefinition;
use Windwalker\DI\ServiceProviderInterface;

use function Windwalker\DI\create;

/**
 * The MonologProvider class.
 *
 * @since  4.0
 */
class MonologProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        //
    }

    public static function rotatingFileHandler(string $channel, ...$args): ObjectBuilderDefinition
    {
        return create(
            RotatingFileHandler::class,
            Runtime::config('@logs') . '/' . $channel . '.log',
            ...$args
        );
    }

    public static function logger(
        ?string $name = null,
        array $handlers = [],
        array $processors = [],
        string $formatter = 'line_formatter'
    ): ObjectBuilderDefinition {
        return create(
            static function (Container $container) use ($formatter, $processors, $handlers, $name) {
                $logger = new Logger(
                    $name,
                    array_map(
                        static function ($handler) use ($formatter, $container, $name) {
                            /** @var HandlerInterface $handler */
                            $handler = $container->resolve($handler, ['instanceName' => $name]);

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
            }
        );
    }
}
