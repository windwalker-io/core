<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\Context\RequestAppContextInterface;
use Windwalker\Core\Application\WebSocket\WsAppContext;
use Windwalker\Core\Application\WebSocket\WsApplicationInterface;
use Windwalker\Core\Application\WebSocket\WsAppRequest;
use Windwalker\Core\Application\WebSocket\WsRootApplicationInterface;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\Http\Browser;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\State\AppState;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Reactor\WebSocket\MessageEmitterInterface;
use Windwalker\Reactor\WebSocket\WebSocketRequest;
use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;
use Windwalker\Reactor\WebSocket\WebSocketServerInterface;

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

        $this->registerRequestObject($container);

        // App Context
        $this->registerAppContext($container);

        // Controller Dispatcher
        $container->prepareSharedObject(ControllerDispatcher::class);

        // // Navigator
        // $container->prepareSharedObject(
        //     Navigator::class,
        //     fn(Navigator $nav, Container $container) => $nav->addEventDealer($container->get(AppContext::class))
        // );

        // Renderer
        // $this->extendRenderer($container);

        // Security
        // $this->registerSecurityServices($container);
    }

    /**
     * @param  Container  $container
     *
     * @return  WsAppRequest
     */
    protected function createAppRequest(Container $container): WsAppRequest
    {
        return $container->newInstance(WsAppRequest::class)
            ->withRequest($container->get(WebSocketRequest::class));
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
            ->alias(RequestAppContextInterface::class, WsAppContext::class);
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
        $container->share(
            WebSocketRequest::class,
            fn() => new WebSocketRequest(),
            Container::ISOLATION
        )
            ->alias(ServerRequestInterface::class, WebSocketRequest::class)
            ->alias(WebSocketRequestInterface::class, WebSocketRequest::class)
            ->alias(ServerRequest::class, WebSocketRequest::class);

        // System Uri
        // $container->share(
        //     SystemUri::class,
        //     function (Container $container) {
        //         return $container->get(ProxyResolver::class)->handleProxyHost(
        //             SystemUri::parseFromRequest($container->get(ServerRequestInterface::class))
        //         );
        //     },
        //     Container::ISOLATION
        // );

        // Proxy
        $container->prepareSharedObject(ProxyResolver::class, null, Container::ISOLATION);

        // App Request
        $container->set(
            WsAppContext::class,
            fn(Container $container) => $container->get(WsAppContext::class)->getAppRequest()
        );

        // Browser Agent Detect
        $container->share(
            Browser::class,
            fn(Container $container) => Browser::fromRequest($container->get(ServerRequest::class))
        );
    }
}
