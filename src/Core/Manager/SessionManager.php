<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\AppType;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Events\Web\AfterRespondEvent;
use Windwalker\Core\Factory\SessionFactory;
use Windwalker\DI\Attributes\Factory;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\DI\Container;
use Windwalker\Session\Bridge\BridgeInterface;
use Windwalker\Session\Bridge\PhpBridge;
use Windwalker\Session\Session;
use Windwalker\Session\SessionInterface;

use function Windwalker\chronos;

/**
 * The SessionManager class.
 *
 * @method Session create(?string $name = null, ...$args)
 * @method Session get(?string $name = null, ...$args)
 *
 * @deprecated  Use container tags instead.
 */
#[Isolation]
class SessionManager extends SessionFactory
{
    public static function createSession(
        string $bridge,
        string $handler,
        string $cookies,
        array $options = []
    ): \Closure {
        return #[Factory] static function (Container $container) use ($cookies, $handler, $bridge, $options) {
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
