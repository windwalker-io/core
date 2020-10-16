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
        $container->share(ServerRequest::class, $this->request)
            ->alias(ServerRequestInterface::class, ServerRequest::class);

        $container->prepareSharedObject(ControllerDispatcher::class);

        $container->prepareSharedObject(AppContext::class, function (AppContext $app, Container $container) {
            return $app->withIsDebug($container->getParam('app.debug'))
                ->withMode($container->getParam('app.mode'))
                ->withRequest($this->request);
        })
            ->alias(ApplicationInterface::class, AppContext::class);
    }
}
