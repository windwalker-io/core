<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\AppLayer;
use Windwalker\Core\Application\AppType;
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\Core\Application\WebApplicationInterface;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\Http\RequestInspector;
use Windwalker\Core\Security\CspNonceService;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Http\Browser;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\State\AppState;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Http\Factory\ServerRequestFactory;
use Windwalker\Http\Request\ServerRequest;

/**
 * The RequestProvider class.
 *
 * @level 2
 */
class WebProvider implements ServiceProviderInterface
{
    /**
     * RequestProvider constructor.
     *
     * @param  WebApplicationInterface  $parentApp
     */
    public function __construct(protected WebApplicationInterface $parentApp)
    {
    }

    public function canProvide(): \Closure
    {
        if ($this->parentApp->getType() === AppType::CONSOLE) {
            return static fn (int $level) => $level > AppLayer::APP;
        }

        return static fn (int $level) => $level > AppLayer::REQUEST;
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
        $this->registerRequestObject($container);

        // App Context
        $this->registerAppContext($container);

        // Controller Dispatcher
        $container->prepareSharedObject(ControllerDispatcher::class)
            ->providedIn($this->canProvide(...));

        // Navigator
        $container->prepareSharedObject(
            Navigator::class,
            fn(Navigator $nav, Container $container) => $nav->addEventDealer($container->get(AppContext::class))
        )->providedIn($this->canProvide(...));

        // Security
        $this->registerSecurityServices($container);
    }

    /**
     * @param  Container  $container
     *
     * @return  AppRequest
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     */
    protected function createAppRequest(Container $container): AppRequest
    {
        return $container->newInstance(AppRequest::class)
            ->withRequest($container->get(ServerRequestInterface::class))
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
            AppContext::class,
            function (AppContext $app, Container $container) {
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
            ->alias(AppContextInterface::class, AppContext::class)
            ->providedIn($this->canProvide(...));
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
            ServerRequest::class,
            function (Container $container) {
                if ($container->has(ServerRequestFactoryInterface::class)) {
                    return $container->get(ServerRequestFactoryInterface::class)
                        ->createServerRequest(
                            $_SERVER['REQUEST_METHOD'],
                            ServerRequestFactory::prepareUri($_SERVER),
                            $_SERVER
                        );
                }

                return ServerRequestFactory::createFromGlobals();
            },
            Container::ISOLATION
        )
            ->alias(ServerRequestInterface::class, ServerRequest::class);

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

        // AjaxInspector
        $container->prepareSharedObject(RequestInspector::class);

        // Proxy
        $container->prepareSharedObject(ProxyResolver::class, null, Container::ISOLATION);

        // App Request
        $container->set(
            AppRequest::class,
            fn(Container $container) => $container->get(AppContext::class)->getAppRequest()
        )
            ->alias(AppRequestInterface::class, AppRequest::class)
            ->providedIn($this->canProvide(...));

        // Browser Agent Detect
        $container->share(
            Browser::class,
            fn(Container $container) => Browser::fromRequest($container->get(ServerRequest::class))
        );
    }

    protected function registerSecurityServices(Container $container): void
    {
        $container->prepareSharedObject(
            CspNonceService::class,
            null,
            Container::ISOLATION
        );
    }
}
