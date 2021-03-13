<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\Service\RendererService;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Http\Request\ServerRequest;

/**
 * The RequestProvider class.
 */
class RequestProvider implements ServiceProviderInterface
{
    /**
     * RequestProvider constructor.
     *
     * @param  ServerRequestInterface  $request
     * @param  WebApplication          $parentApp
     */
    public function __construct(protected ServerRequestInterface $request, protected WebApplication $parentApp)
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
        // Request
        $container->share(ServerRequest::class, $this->request)
            ->alias(ServerRequestInterface::class, ServerRequest::class);

        // Controller Dispatcher
        $container->prepareSharedObject(ControllerDispatcher::class);

        // System Uri
        $container->share(SystemUri::class, fn() => SystemUri::parseFromRequest($this->request));

        // App Request
        $container->prepareSharedObject(
            AppRequest::class,
            function (AppRequest $request, Container $container) {
                return $request->withRequest($this->request)
                    ->withSystemUri($container->get(SystemUri::class));
            }
        );

        // App Context
        $container->prepareSharedObject(
            AppContext::class,
            function (AppContext $app, Container $container) {
                $this->parentApp->getEventDispatcher()->addDealer($app->getEventDispatcher());

                return $app->withIsDebug((bool) $container->getParam('app.debug'))
                    ->withMode((string) $container->getParam('app.mode'))
                    ->withAppRequest($container->get(AppRequest::class));
            }
        )
            ->alias(ApplicationInterface::class, AppContext::class);

        // Navigator
        $container->prepareSharedObject(Navigator::class);

        // Renderer
        $container->extend(
            RendererService::class,
            function (RendererService $service, Container $container) {
                return $service->addGlobal('app', $app = $container->get(AppContext::class))
                    ->addGlobal('uri', $app->getSystemUri())
                    ->addGlobal('nav', $container->get(Navigator::class));
            }
        );
    }
}
