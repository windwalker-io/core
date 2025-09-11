<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Windwalker\Core\Runtime\Runtime;
use Windwalker\DI\Attributes\Factory;
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

    /**
     * @param  string  $channel
     * @param          ...$args
     *
     * @return  ObjectBuilderDefinition
     *
     * @deprecated  Use LoggerFactory::rotatingFileHandler() instead
     */
    public static function rotatingFileHandler(string $channel, ...$args): ObjectBuilderDefinition
    {
        return create(
            RotatingFileHandler::class,
            Runtime::config('@logs') . '/' . $channel . '.log',
            ...$args
        );
    }

    /**
     * @param  string|null  $name
     * @param  array        $handlers
     * @param  array        $processors
     * @param  string       $formatter
     *
     * @return  \Closure
     *
     * @deprecated  Use LoggerFactory::monolog() instead
     */
    public static function logger(
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
        };
    }
}
