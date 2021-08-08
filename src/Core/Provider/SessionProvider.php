<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\Ref;
use Windwalker\Core\Manager\SessionManager;
use Windwalker\Core\Security\CsrfService;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Http\Event\ResponseEvent;
use Windwalker\Session\Cookie\ArrayCookies;
use Windwalker\Session\Cookie\Cookies;
use Windwalker\Session\Session;
use Windwalker\Session\SessionInterface;

/**
 * The SessionProvider class.
 */
class SessionProvider implements ServiceProviderInterface
{
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
        $container->prepareSharedObject(SessionManager::class);

        // Cookies
        $container->prepareSharedObject(Cookies::class);
        $container->prepareObject(ArrayCookies::class);

        $container->bind(Session::class, fn(SessionManager $manager) => $manager->get())
            ->alias(SessionInterface::class, Session::class);
        $container->prepareSharedObject(CsrfService::class);
    }

    public static function psrCookies(): callable
    {
        return function (ServerRequestInterface $request, AppContext $app, #[Ref('session.cookie_params')] $params) {
            $cookies = new ArrayCookies($request->getCookieParams());
            $cookies->setOptions($params);

            $app->getRootApp()->on(
                'response',
                function (ResponseEvent $event) use ($cookies) {
                    $res = $event->getResponse();

                    foreach ($cookies->getCookieHeaders() as $header) {
                        $res = $res->withAddedHeader('Set-Cookie', $header);
                    }

                    $event->setResponse($res);
                }
            );

            return $cookies;
        };
    }
}
