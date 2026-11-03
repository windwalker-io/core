<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Session;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Session\CookiesAutoSecureSubscriber;
use Windwalker\DI\Container;
use Windwalker\Session\Cookie\CookiesConfigurableInterface;
use Windwalker\Session\Cookie\CookiesInterface;
use Windwalker\Session\Cookie\CookiesOptions;
use DateTimeInterface;

class CookiesAutoSecureSubscriberTest extends TestCase
{
    private function createRequestStub(string $scheme): ServerRequestInterface
    {
        $uri = $this->createStub(UriInterface::class);
        $uri->method('getScheme')->willReturn($scheme);

        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);

        return $request;
    }

    #[Test]
    public function setsSecureWhenEnabledAndRequestIsHttps(): void
    {
        $cookies = new FakeConfigurableCookies();

        $container = new Container();
        $container->share(CookiesInterface::class, fn() => $cookies);
        $container->share(ServerRequestInterface::class, fn() => $this->createRequestStub('https'));

        $subscriber = new CookiesAutoSecureSubscriber(enabled: true);
        $subscriber->beforeRequest(new BeforeRequestEvent($container));

        // Resolving CookiesInterface triggers the registered extend() closure.
        $container->get(CookiesInterface::class);

        self::assertTrue($cookies->secureCalledWith);
    }

    #[Test]
    public function doesNotSetSecureWhenRequestIsNotHttps(): void
    {
        $cookies = new FakeConfigurableCookies();

        $container = new Container();
        $container->share(CookiesInterface::class, fn() => $cookies);
        $container->share(ServerRequestInterface::class, fn() => $this->createRequestStub('http'));

        $subscriber = new CookiesAutoSecureSubscriber(enabled: true);
        $subscriber->beforeRequest(new BeforeRequestEvent($container));

        $container->get(CookiesInterface::class);

        self::assertNull($cookies->secureCalledWith);
    }

    #[Test]
    public function doesNotRegisterExtendWhenDisabled(): void
    {
        $cookies = $this->createStub(CookiesInterface::class);

        $container = new Container();
        $container->share(CookiesInterface::class, fn() => $cookies);

        $subscriber = new CookiesAutoSecureSubscriber(enabled: false);
        $subscriber->beforeRequest(new BeforeRequestEvent($container));

        // No extend was registered, so the original stub is returned as-is.
        self::assertSame($cookies, $container->get(CookiesInterface::class));
    }

    #[Test]
    public function doesNotRegisterExtendWhenContainerHasNoCookiesInterface(): void
    {
        $container = new Container();

        $subscriber = new CookiesAutoSecureSubscriber(enabled: true);
        $subscriber->beforeRequest(new BeforeRequestEvent($container));

        self::assertFalse($container->has(CookiesInterface::class));
    }

    #[Test]
    public function throwsExceptionWhenCookiesIsNotConfigurable(): void
    {
        $cookies = $this->createStub(CookiesInterface::class);

        $container = new Container();
        $container->share(CookiesInterface::class, fn() => $cookies);
        $container->share(ServerRequestInterface::class, fn() => $this->createRequestStub('https'));

        $subscriber = new CookiesAutoSecureSubscriber(enabled: true);
        $subscriber->beforeRequest(new BeforeRequestEvent($container));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Auto secure cookies must instance of/');

        $container->get(CookiesInterface::class);
    }
}

/**
 * Minimal double implementing both CookiesInterface and
 * CookiesConfigurableInterface, used to verify secure() calls. A real class
 * is used instead of a mock because the two interfaces share an identical
 * getStorage() signature, which PHPUnit's intersection mock generator rejects.
 */
class FakeConfigurableCookies implements CookiesInterface, CookiesConfigurableInterface
{
    public ?bool $secureCalledWith = null;

    public function set(string $name, string $value, CookiesOptions|array|null $options = null): bool
    {
        return true;
    }

    public function get(string $name): ?string
    {
        return null;
    }

    public function remove(string $name): bool
    {
        return true;
    }

    public function getStorage(): array
    {
        return [];
    }

    public function expires(int|string|DateTimeInterface $expires): static
    {
        return $this;
    }

    public function path(string $path): static
    {
        return $this;
    }

    public function domain(string $domain): static
    {
        return $this;
    }

    public function secure(bool $secure): static
    {
        $this->secureCalledWith = $secure;

        return $this;
    }

    public function httpOnly(bool $httpOnly): static
    {
        return $this;
    }

    public function sameSite(string $sameSite): static
    {
        return $this;
    }
}
