<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Session;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Http\Browser;
use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\Session\Cookie\CookiesConfigurableInterface;
use Windwalker\Session\Cookie\CookiesInterface;
use Windwalker\Session\Session;

/**
 * The SessionSubscriber class.
 */
#[EventSubscriber]
class CookiesAutoSecureSubscriber
{
    public function __construct(protected bool $enabled = true)
    {
        //
    }

    #[ListenTo(BeforeRequestEvent::class)]
    public function beforeRequest(BeforeRequestEvent $event): void
    {
        $container = $event->getContainer();

        if ($this->enabled && $container->has(CookiesInterface::class)) {
            $container->extend(
                CookiesInterface::class,
                function (CookiesInterface $cookies) use ($container) {
                    $request = $container->get(ServerRequestInterface::class);

                    if (!$cookies instanceof CookiesConfigurableInterface) {
                        throw new \LogicException(
                            sprintf(
                                'Auto secure cookies must instance of: %s, %s given.',
                                CookiesConfigurableInterface::class,
                                $cookies::class
                            )
                        );
                    }

                    if ($request->getUri()->getScheme() === 'https') {
                        $cookies->secure(true);
                    }

                    return $cookies;
                }
            );
        }
    }
}
