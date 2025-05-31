<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\AppType;
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Core\Attributes\Ref;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Events\Web\AfterRequestEvent;
use Windwalker\Core\Events\Web\AfterRespondEvent;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\DI\Container;
use Windwalker\Session\Bridge\BridgeInterface;
use Windwalker\Session\Bridge\PhpBridge;
use Windwalker\Session\Cookie\ArrayCookies;
use Windwalker\Session\Cookie\Cookies;
use Windwalker\Session\Cookie\CookiesInterface;
use Windwalker\Session\Session;
use Windwalker\Session\SessionInterface;

use function Windwalker\chronos;

/**
 * The SessionManager class.
 *
 * @method Session create(?string $name = null, ...$args)
 * @method Session get(?string $name = null, ...$args)
 */
#[Isolation]
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
            AppContextInterface $app,
            #[Ref('session.cookie_params')] $params
        ) {
            $cookies = new ArrayCookies($request->getCookieParams());
            $cookies->setOptions($params);

            $app->on(
                AfterRequestEvent::class,
                function (AfterRequestEvent $event) use ($cookies) {
                    $res = $event->response;

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
            AppContextInterface $app,
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
            /** @var BridgeInterface $bridge */
            $cookies = $container->resolve('session.factories.cookies.' . $cookies);
            $sessionOptions = $container->getParam('session.session_options') ?? [];
            $cookieParams = $container->getParam('session.cookie_params') ?? [];

            $options = [
                ...$options,
                ...$sessionOptions
            ];

            if ($bridge instanceof PhpBridge) {
                $gcDivisor = $sessionOptions['gc_divisor'] ?? 1000;
                $gcProbability = $sessionOptions['gc_probability'] ?? 1;
                $gcMaxlifetime = $sessionOptions['gc_maxlifetime'] ?? null;

                if (!$gcMaxlifetime && $cookieParams['expires'] ?? null) {
                    $expires = chronos($cookieParams['expires']);
                    $gcMaxlifetime = Chronos::intervalToSeconds(chronos('now')->diff($expires));
                }

                $bridge->setOption('gc_divisor', $gcDivisor);
                $bridge->setOption('gc_probability', $gcProbability);
                $bridge->setOption('gc_maxlifetime', $gcMaxlifetime);
            }

            if ($sessionOptions['name'] ?? null) {
                $bridge->setSessionName($sessionOptions['name']);
            }

            $app = $container->get(ApplicationInterface::class);

            if ($app->getType() === AppType::CLI_WEB) {
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
