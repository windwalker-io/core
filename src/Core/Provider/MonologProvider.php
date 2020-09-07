<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Provider;

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
 * @since  __DEPLOY_VERSION__
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
        array $processors = []
    ): ObjectBuilderDefinition {
        return create(
            function (Container $container) use ($processors, $handlers, $name) {
                return new Logger(
                    $name,
                    array_map(fn($handler) => $container->resolve($handler, ['_name' => $name]), $handlers),
                    array_map(fn($processor) => $container->resolve($processor, ['_name' => $name]), $processors)
                );
            }
        );
    }
}
