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
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;
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
     */
    public function __construct(protected ServerRequestInterface $request)
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

        // App Context
        $container->prepareSharedObject(AppContext::class, function (AppContext $app, Container $container) {
            return $app->withIsDebug((bool) $container->getParam('app.debug'))
                ->withMode((string) $container->getParam('app.mode'))
                ->withRequest($this->request)
                ->withSystemUri($container->get(SystemUri::class));
        })
            ->alias(ApplicationInterface::class, AppContext::class);

        // Navigator
        $container->prepareSharedObject(Navigator::class);
    }
}
