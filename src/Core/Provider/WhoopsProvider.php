<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;
use Whoops\Handler\CallbackHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\BootableProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\Definition\ObjectBuilderDefinition;
use Windwalker\DI\ServiceProviderInterface;

use function Windwalker\DI\create;

/**
 * The WhoopsProvider class.
 */
class WhoopsProvider implements ServiceProviderInterface, BootableProviderInterface
{
    /**
     * boot
     *
     * @param  Container  $container
     *
     * @return  void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function boot(Container $container): void
    {
        if (!$container->getParam('app.debug')) {
            return;
        }

        // $error = $container->get(ErrorService::class);
        //
        // $whoops = $container->get(Run::class);
        //
        // foreach ($container->getParam('whoops.factories.handlers') as $handler) {
        //     $whoops->pushHandler($container->resolve($handler));
        // }
        //
        // $error->addHandler(
        //     function (Throwable $e) use ($whoops) {
        //         $whoops->allowQuit(false);
        //         $whoops->handleException($e);
        //     },
        //     'default'
        // );
    }

    /**
     * Registers the service provider with a DI container.
     *
     * @param  Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        //
    }

    public static function prettyPageHandler(): ObjectBuilderDefinition
    {
        return create(PrettyPageHandler::class)
            ->extend(function (PrettyPageHandler $handler, Container $container) {
                foreach ((array) $container->getParam('whoops.hidden_list') as $type => $keys) {
                    foreach ((array) $keys as $key) {
                        $handler->hideSuperglobalKey($type, $key);
                    }
                }

                $handler->setEditor($container->getParam('whoops.editor') ?? 'phpstorm');

                return $handler;
            });
    }

    public static function cliServerHandler(): ObjectBuilderDefinition
    {
        return create(
            function (Container $container) {
                return new CallbackHandler(
                    function ($exception, $inspector, $run) use ($container) {
                        $app = $container->get(ApplicationInterface::class);

                        if (
                            PHP_SAPI === 'cli'
                            && $app->getClient() === ApplicationInterface::CLIENT_WEB
                        ) {
                            $textHandler = new PlainTextHandler();
                            $textHandler->addPreviousToOutput(true);
                            $textHandler->addTraceToOutput(true);
                            $textHandler->setException($exception);
                            $textHandler->setInspector($inspector);
                            $textHandler->setRun($run);
                            $message = $textHandler->generateResponse();

                            echo $message;
                        }
                    }
                );
            }
        );
    }
}
