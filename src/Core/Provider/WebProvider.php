<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Jenssegers\Agent\Agent;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Http\Browser;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\State\AppState;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Http\Request\ServerRequestFactory;

/**
 * The RequestProvider class.
 */
class WebProvider implements ServiceProviderInterface
{
    /**
     * RequestProvider constructor.
     *
     * @param  WebApplication  $parentApp
     */
    public function __construct(protected WebApplication $parentApp)
    {
    }

    /**
     * Registers the service provider with a DI container.
     *
     * @param  Container  $container  The DI container.
     *
     * @return  void
     * @throws \Windwalker\DI\Exception\DefinitionException
     */
    public function register(Container $container): void
    {
        $this->registerRequestObject($container);

        // App Context
        $this->registerAppContext($container);

        // Controller Dispatcher
        $container->prepareSharedObject(ControllerDispatcher::class);

        // Navigator
        $container->prepareSharedObject(Navigator::class);

        // Renderer
        $this->extendRenderer($container);
    }

    /**
     * createAppRequest
     *
     * @param  Container  $container
     *
     * @return  AppRequest
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
     * @throws \Windwalker\DI\Exception\DefinitionException
     */
    protected function registerAppContext(Container $container): void
    {
        $container->prepareSharedObject(AppState::class);
        $container->prepareSharedObject(
            AppContext::class,
            function (AppContext $app, Container $container) {
                $this->parentApp->getEventDispatcher()->addDealer($app->getEventDispatcher());

                return $app->setAppRequest($this->createAppRequest($container))
                    ->setState($container->get(AppState::class));
            }
        )
            ->alias(ApplicationInterface::class, AppContext::class);
    }

    /**
     * registerRequestObject
     *
     * @param  Container  $container
     *
     * @return  void
     *
     * @throws \Windwalker\DI\Exception\DefinitionException
     */
    protected function registerRequestObject(Container $container): void
    {
        // Request
        $container->share(ServerRequest::class, fn() => ServerRequestFactory::createFromGlobals())
            ->alias(ServerRequestInterface::class, ServerRequest::class);

        // System Uri
        $container->share(
            SystemUri::class,
            fn(Container $container) => SystemUri::parseFromRequest($container->get(ServerRequestInterface::class))
        );

        // App Request
        $container->share(
            AppRequest::class,
            fn(Container $container) => $container->get(AppContext::class)->getAppRequest()
        );

        // Browser Agent Detect
        $container->share(
            Browser::class,
            fn(Container $container) => Browser::fromRequest($container->get(ServerRequest::class))
        );
    }

    /**
     * extendRenderer
     *
     * @param  Container  $container
     *
     * @return  void
     */
    protected function extendRenderer(Container $container): void
    {
        // $container->extend(
        //     RendererService::class,
        //     function (RendererService $service, Container $container) {
        //         return $service->addGlobal('app', $app = $container->get(AppContext::class))
        //             ->addGlobal('uri', $app->getSystemUri())
        //             ->addGlobal('nav', $container->get(Navigator::class));
        //     }
        // );
    }
}
