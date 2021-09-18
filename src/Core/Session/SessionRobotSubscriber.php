<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Session;

use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Http\Browser;
use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\Session\Session;

/**
 * The SessionSubscriber class.
 */
#[EventSubscriber]
class SessionRobotSubscriber
{
    public function __construct(protected string $profileName = 'null')
    {
        //
    }

    #[ListenTo(BeforeRequestEvent::class)]
    public function beforeRequest(BeforeRequestEvent $event): void
    {
        $container = $event->getContainer();

        $browser = $container->get(Browser::class);

        if ($browser->isRobot() && $container->has(Session::class)) {
            $container->getParameters()->setDeep('session.default', $this->profileName);
        }
    }
}
