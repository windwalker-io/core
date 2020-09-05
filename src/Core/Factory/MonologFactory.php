<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Factory;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Windwalker\Core\Runtime\Runtime;
use Windwalker\DI\Container;
use Windwalker\DI\Definition\ObjectBuilderDefinition;

use function Windwalker\DI\create;

/**
 * The MonologFactory class.
 */
class MonologFactory
{
    public static function logger(
        ?string $name = null,
        array $handlers = [],
        array $processors = []
    ): ObjectBuilderDefinition {
        return create(
            function (Container $container) use ($processors, $handlers, $name) {
                return new Logger(
                    $name,
                    array_map(fn($handler) => $container->resolve($handler), $handlers),
                    array_map(fn($processor) => $container->resolve($processor), $processors)
                );
            }
        );
    }

    public static function rotatingFileHandler(string $channel, ...$args): ObjectBuilderDefinition
    {
        return create(
            RotatingFileHandler::class,
            Runtime::getRootDir() . '/logs/' . $channel . '.log',
            ...$args
        );
    }
}
