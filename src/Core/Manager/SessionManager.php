<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\Ref;
use Windwalker\Core\Http\Browser;
use Windwalker\DI\Container;
use Windwalker\Http\Event\ResponseEvent;
use Windwalker\Session\Cookie\ArrayCookies;
use Windwalker\Session\Session;

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

    public static function createSession(string $bridge, string $handler, string $cookies, array $options = []): \Closure
    {
        return function (Container $container) use ($cookies, $handler, $bridge, $options) {
            $bridge = $container->resolve(
                'session.factories.bridges.' . $bridge,
                [
                    'handler' => $container->resolve('session.factories.handlers.' . $handler)
                ]
            );
            $cookies = $container->resolve('session.factories.cookies.' . $cookies);
            $ini = $container->getParam('session.ini');

            $options['ini'] = $ini;

            return new Session($options, $bridge, $cookies);
        };
    }
}
