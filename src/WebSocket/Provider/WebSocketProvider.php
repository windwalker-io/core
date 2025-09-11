<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Provider;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\Core\CliServer\CliServerRuntime;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\Http\Browser;
use Windwalker\Core\Http\BrowserNext;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\State\AppState;
use Windwalker\DI\BootableProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\DIOptions;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Reactor\Memory\MemoryTableFactory;
use Windwalker\Reactor\Swoole\Room\RoomMapping;
use Windwalker\Reactor\Swoole\Room\UserFdMapping;
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
use Windwalker\WebSocket\Swoole\RequestRegistry;

/**
 * The WebSocketProvider class.
 *
 * @level 2
 */
class WebSocketProvider implements ServiceProviderInterface, BootableProviderInterface
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
     * Create mapping object that can share cross processes.
     *
     * @param  Container  $container
     *
     * @return  void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function boot(Container $container): void
    {
        $container->get(UserFdMapping::class);
        $container->get(RoomMapping::class);
        $container->get(RequestRegistry::class);
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

        $this->registerRequestObject($container);

        // App Context
        $this->registerAppContext($container);

        // Controller Dispatcher
        $container->prepareSharedObject(ControllerDispatcher::class);

        // Memory Tables
        $this->registerMemoryTables($container);
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
            new DIOptions(isolation: true)
        );

        // Proxy
        $container->prepareSharedObject(ProxyResolver::class, null, new DIOptions(isolation: true));

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
        $container->share(
            BrowserNext::class,
            fn(Container $container) => BrowserNext::fromRequest($container->get(ServerRequest::class))
        );
    }

    protected function registerMemoryTables(Container $container): void
    {
        $container->prepareSharedObject(MemoryTableFactory::class);
        $container->share(
            UserFdMapping::class,
            fn(Container $container) => $container->get(MemoryTableFactory::class)
                ->createUserFdMapping(
                    $this->parentApp->config('reactor.websocket.user_mapping.size') ?? 1024,
                    $this->parentApp->config('reactor.websocket.user_mapping.length') ?? 32768,
                )
        );
        $container->share(
            RoomMapping::class,
            fn(Container $container) => $container->get(MemoryTableFactory::class)
                ->createRoomMapping(
                    $container->get(UserFdMapping::class),
                    $this->parentApp->config('reactor.websocket.user_mapping.size') ?? 1024,
                    $this->parentApp->config('reactor.websocket.user_mapping.length') ?? 32768,
                )
        );
        $container->share(
            RequestRegistry::class,
            function (Container $container) {
                $options = CliServerRuntime::getServerState()->getStartupOptions();

                return new RequestRegistry(
                    $container->get(MemoryTableFactory::class)->createMemoryTable(
                        // Default size is same as `ulimit -n`,
                        // @see https://wiki.swoole.com/#/server/setting?id=max_conn-max_connection
                        $this->parentApp->config('reactor.websocket.request_registry.size')
                            ?? $options['max_requests'] ?? 100000
                    ),
                    $this->parentApp->config('reactor.websocket.request_registry.length') ?? 2048
                );
            }
        );
    }
}
