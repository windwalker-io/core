<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Attributes\Ref;
use Windwalker\Core\Events\Web\AfterRequestEvent;
use Windwalker\Core\Events\Web\AfterRespondEvent;
use Windwalker\DI\Container;
use Windwalker\Session\Cookie\ArrayCookies;
use Windwalker\Session\Cookie\Cookies;
use Windwalker\Session\Session;
use Windwalker\Session\SessionInterface;

/**
 * The SessionManager class.
 *
 * @method Session create(?string $name = null, ...$args)
 * @method Session get(?string $name = null, ...$args)
 */
class SessionManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'session';
    }

    public static function psrCookies(): callable
    {
        return static function (
            ServerRequestInterface $request,
            AppContext $app,
            #[Ref('session.cookie_params')] $params
        ) {
            $cookies = new ArrayCookies($request->getCookieParams());
            $cookies->setOptions($params);

            $app->on(
                AfterRequestEvent::class,
                function (AfterRequestEvent $event) use ($cookies) {
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

    public static function nativeCookies(): callable
    {
        return static function (
            ServerRequestInterface $request,
            AppContext $app,
            #[Ref('session.cookie_params')] $params
        ) {
            $cookies = new Cookies();
            $cookies->setOptions($params);

            return $cookies;
        };
    }

    public static function createSession(
        string $bridge,
        string $handler,
        string $cookies,
        array $options = []
    ): Closure {
        return static function (Container $container) use ($cookies, $handler, $bridge, $options) {
            $bridge = $container->resolve(
                'session.factories.bridges.' . $bridge,
                [
                    'handler' => $container->resolve('session.factories.handlers.' . $handler),
                ]
            );
            $cookies = $container->resolve('session.factories.cookies.' . $cookies);
            $ini = $container->getParam('session.ini');

            $options['ini'] = $ini;

            $app = $container->get(ApplicationInterface::class);

            if ($app->getClientType() === 'cli_web') {
                // In cli web, disable shutdown function to save memory
                $options[SessionInterface::OPTION_AUTO_COMMIT] = false;
            }

            $session = new Session($options, $bridge, $cookies);

            $app->on(AfterRespondEvent::class, function (AfterRespondEvent $event) use ($session) {
                if ($session->isStarted()) {
                    $session->stop();
                }
            });

            return $session;
        };
    }
}
