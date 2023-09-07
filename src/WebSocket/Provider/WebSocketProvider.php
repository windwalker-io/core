<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\WebSocket\Provider;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\Http\Browser;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Router\Router;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\State\AppState;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Reactor\WebSocket\MessageEmitterInterface;
use Windwalker\Reactor\WebSocket\WebSocketRequest;
use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;
use Windwalker\Reactor\WebSocket\WebSocketServerInterface;
use Windwalker\WebSocket\Application\WsAppContext;
use Windwalker\WebSocket\Application\WsApplicationInterface;
use Windwalker\WebSocket\Application\WsAppRequest;
use Windwalker\WebSocket\Application\WsRootApplicationInterface;
use Windwalker\WebSocket\Parser\SimpleMessageParser;
use Windwalker\WebSocket\Parser\WebSocketParserInterface;
use Windwalker\WebSocket\Router\WsRouter;

/**
 * The WebSocketProvider class.
 *
 * @level 2
 */
class WebSocketProvider implements ServiceProviderInterface
{
    /**
     * RequestProvider constructor.
     *
     * @param  WsApplicationInterface  $parentApp
     */
    public function __construct(protected WsApplicationInterface $parentApp)
    {
    }

    /**
     * Registers the service provider with a DI container.
     *
     * @param  Container  $container  The DI container.
     *
     * @return  void
     * @throws DefinitionException
     *
     * @level 2
     */
    public function register(Container $container): void
    {
        $container->share(WsRootApplicationInterface::class, $this->parentApp);
        $container->share(
            MessageEmitterInterface::class,
            fn (Container $container) => $container->get(WebSocketServerInterface::class)->getMessageEmitter()
        );

        $container->bindShared(WebSocketParserInterface::class, SimpleMessageParser::class);

        // Router
        $container->prepareSharedObject(
            WsRouter::class,
            function (WsRouter $router, Container $container) {
                return $router->register($container->getParam('routing.routes'));
            }
        )
            ->alias(Router::class, WsRouter::class);

        $this->registerRequestObject($container);

        // App Context
        $this->registerAppContext($container);

        // Controller Dispatcher
        $container->prepareSharedObject(ControllerDispatcher::class);
    }

    /**
     * @param  Container  $container
     *
     * @return  WsAppRequest
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function createAppRequest(Container $container): WsAppRequest
    {
        return $container->newInstance(WsAppRequest::class)
            ->withServerRequest($container->get(WebSocketRequest::class))
            ->withSystemUri($container->get(SystemUri::class));
    }

    /**
     * registerAppContext
     *
     * @param  Container  $container
     *
     * @return  void
     *
     * @throws DefinitionException
     */
    protected function registerAppContext(Container $container): void
    {
        $container->prepareSharedObject(AppState::class);
        $container->prepareSharedObject(
            WsAppContext::class,
            function (WsAppContext $app, Container $container) {
                // if ($container->getLevel() === 2) {
                //     throw new \LogicException(
                //         'AppContext should not create in a level 2 Container.'
                //     );
                // }

                // $this->parentApp->getEventDispatcher()->addDealer($app->getEventDispatcher());

                return $app->setAppRequest($this->createAppRequest($container))
                    ->setState($container->get(AppState::class));
            }
        )
            ->alias(AppContextInterface::class, WsAppContext::class);
    }

    /**
     * registerRequestObject
     *
     * @param  Container  $container
     *
     * @return  void
     *
     * @throws DefinitionException
     */
    protected function registerRequestObject(Container $container): void
    {
        // Request
        $container->alias(ServerRequestInterface::class, WebSocketRequest::class)
            ->alias(WebSocketRequestInterface::class, WebSocketRequest::class)
            ->alias(ServerRequest::class, WebSocketRequest::class);

        // System Uri
        $container->share(
            SystemUri::class,
            function (Container $container) {
                return $container->get(ProxyResolver::class)->handleProxyHost(
                    SystemUri::parseFromRequest($container->get(ServerRequestInterface::class))
                );
            },
            Container::ISOLATION
        );

        // Proxy
        $container->prepareSharedObject(ProxyResolver::class, null, Container::ISOLATION);

        // App Request
        $container->set(
            WsAppRequest::class,
            fn(Container $container) => $container->get(WsAppContext::class)->getAppRequest()
        )
            ->alias(AppRequestInterface::class, WsAppRequest::class);

        // Browser Agent Detect
        $container->share(
            Browser::class,
            fn(Container $container) => Browser::fromRequest($container->get(ServerRequest::class))
        );
    }
}
