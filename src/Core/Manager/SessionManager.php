<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\Ref;
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
}
