<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Session;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Http\BrowserNext;
use Windwalker\Core\Session\SessionRobotSubscriber;
use Windwalker\DI\Container;
use Windwalker\Session\Session;

class SessionRobotSubscriberTest extends TestCase
{
    #[Test]
    public function setsSessionDefaultProfileWhenRobotAndSessionBound(): void
    {
        $browser = $this->createStub(BrowserNext::class);
        $browser->method('isRobot')->willReturn(true);

        $container = new Container();
        $container->share(BrowserNext::class, fn() => $browser);
        $container->share(Session::class, fn() => $this->createStub(Session::class));

        $subscriber = new SessionRobotSubscriber(profileName: 'robot');
        $subscriber->beforeRequest(new BeforeRequestEvent($container));

        self::assertSame('robot', $container->getParameters()->getDeep('session.default'));
    }

    #[Test]
    public function doesNotSetProfileWhenNotRobot(): void
    {
        $browser = $this->createStub(BrowserNext::class);
        $browser->method('isRobot')->willReturn(false);

        $container = new Container();
        $container->share(BrowserNext::class, fn() => $browser);
        $container->share(Session::class, fn() => $this->createStub(Session::class));

        $subscriber = new SessionRobotSubscriber(profileName: 'robot');
        $subscriber->beforeRequest(new BeforeRequestEvent($container));

        self::assertNull($container->getParameters()->getDeep('session.default'));
    }

    #[Test]
    public function doesNotSetProfileWhenSessionNotBoundEvenIfRobot(): void
    {
        $browser = $this->createStub(BrowserNext::class);
        $browser->method('isRobot')->willReturn(true);

        $container = new Container();
        $container->share(BrowserNext::class, fn() => $browser);

        $subscriber = new SessionRobotSubscriber(profileName: 'robot');
        $subscriber->beforeRequest(new BeforeRequestEvent($container));

        self::assertNull($container->getParameters()->getDeep('session.default'));
    }
}
