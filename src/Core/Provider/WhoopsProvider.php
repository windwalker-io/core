<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 LYRASOFT.
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
use Windwalker\Core\DI\RequestBootableProviderInterface;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\BootableProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\Definition\ObjectBuilderDefinition;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Http\Output\OutputInterface;
use Windwalker\Http\Output\StreamOutput;
use Windwalker\Http\Response\HtmlResponse;

use function Windwalker\DI\create;

/**
 * The WhoopsProvider class.
 */
class WhoopsProvider implements ServiceProviderInterface, BootableProviderInterface, RequestBootableProviderInterface
{
    public function boot(Container $container): void
    {
        $app = $container->get(ApplicationInterface::class);

        if ($app->getClientType() === 'web') {
            $this->replaceErrorHandler($container);
        }
    }

    /**
     * bootBeforeRequest
     *
     * @param  Container  $container
     *
     * @return  void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function bootBeforeRequest(Container $container): void
    {
        $app = $container->get(ApplicationInterface::class);

        if ($app->getClientType() === 'cli_web') {
            $this->replaceErrorHandler($container);
        }
    }

    protected function replaceErrorHandler(Container $container): void
    {
        if (!$container->getParam('app.debug')) {
            return;
        }

        $error = $container->get(ErrorService::class);

        $whoops = $container->get(Run::class);

        foreach ($container->getParam('whoops.factories.handlers') as $handler) {
            $whoops->pushHandler($container->resolve($handler));
        }

        $error->addHandler(
            function (Throwable $e) use ($container, $whoops) {
                $this->handleException($whoops, $e, $container);
            },
            'default'
        );
    }

    public function handleException(Run $whoops, Throwable $e, Container $container): void
    {
        $whoops->allowQuit(false);

        ob_start();
        $whoops->handleException($e);

        $html = ob_get_clean();

        if ($container->has(OutputInterface::class)) {
            $output = $container->get(OutputInterface::class);
        } else {
            $output = new StreamOutput();
        }

        $output->respond(HtmlResponse::fromString($html, $e->getCode()));
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

                $handler->handleUnconditionally(true);
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
