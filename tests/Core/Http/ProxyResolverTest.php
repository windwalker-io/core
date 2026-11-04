<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Http;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\Runtime\Config;
use Windwalker\Http\Request\ServerRequest;

class ProxyResolverTest extends TestCase
{
    private function createResolver(
        array $httpConfig = [],
        array $serverParams = [],
        array $headers = [],
    ): ProxyResolver {
        $config = new Config(['http' => $httpConfig]);

        $request = new ServerRequest(
            serverParams: $serverParams,
            headers: $headers,
        );

        return new ProxyResolver($config, $request);
    }

    #[Test]
    public function getHeaderReturnsNullWhenHeaderNotTrusted(): void
    {
        $resolver = $this->createResolver(
            httpConfig: ['trusted_headers' => []],
            headers: ['X-Forwarded-For' => ['1.2.3.4']],
        );

        self::assertNull($resolver->getHeader('forwarded-for'));
    }

    #[Test]
    public function getHeaderReturnsValueWhenHeaderTrusted(): void
    {
        $resolver = $this->createResolver(
            httpConfig: ['trusted_headers' => ['x-forwarded-for']],
            headers: ['X-Forwarded-For' => ['1.2.3.4']],
        );

        self::assertSame('1.2.3.4', $resolver->getHeader('forwarded-for'));
    }

    #[Test]
    public function isProxyIsTrueOnlyWhenForwardedForHeaderIsTrustedAndPresent(): void
    {
        $withHeader = $this->createResolver(
            httpConfig: ['trusted_headers' => ['x-forwarded-for']],
            headers: ['X-Forwarded-For' => ['1.2.3.4']],
        );
        $withoutHeader = $this->createResolver(
            httpConfig: ['trusted_headers' => []],
            headers: ['X-Forwarded-For' => ['1.2.3.4']],
        );

        self::assertTrue($withHeader->isProxy());
        self::assertFalse($withoutHeader->isProxy());
    }

    #[Test]
    public function getForwardedIpReturnsFirstValidIpFromHeader(): void
    {
        $resolver = $this->createResolver(
            httpConfig: ['trusted_headers' => ['x-forwarded-for']],
            headers: ['X-Forwarded-For' => ['1.2.3.4, not-an-ip, 5.6.7.8']],
        );

        self::assertSame('1.2.3.4', $resolver->getForwardedIP());
    }

    #[Test]
    public function getForwardedIpsReturnsEmptyArrayWhenHeaderMissing(): void
    {
        $resolver = $this->createResolver(httpConfig: ['trusted_headers' => ['x-forwarded-for']]);

        self::assertSame([], $resolver->getForwardedIPs());
    }

    #[Test]
    public function getForwardedIpsFiltersOutInvalidIps(): void
    {
        $resolver = $this->createResolver(
            httpConfig: ['trusted_headers' => ['x-forwarded-for']],
            headers: ['X-Forwarded-For' => ['1.2.3.4, garbage, 5.6.7.8']],
        );

        self::assertSame([0 => '1.2.3.4', 2 => '5.6.7.8'], $resolver->getForwardedIPs());
    }

    #[Test]
    public function getIpsChainAppendsRemoteAddrAfterForwardedIps(): void
    {
        $resolver = $this->createResolver(
            httpConfig: ['trusted_headers' => ['x-forwarded-for']],
            serverParams: ['REMOTE_ADDR' => '9.9.9.9'],
            headers: ['X-Forwarded-For' => ['1.2.3.4, 5.6.7.8']],
        );

        self::assertSame(['1.2.3.4', '5.6.7.8', '9.9.9.9'], $resolver->getIpsChain());
    }

    #[Test]
    public function getNearestClientAndServerIp(): void
    {
        $resolver = $this->createResolver(
            httpConfig: ['trusted_headers' => ['x-forwarded-for']],
            serverParams: ['REMOTE_ADDR' => '9.9.9.9'],
            headers: ['X-Forwarded-For' => ['1.2.3.4, 5.6.7.8']],
        );

        self::assertSame('1.2.3.4', $resolver->getNearestClientIp());
        self::assertSame('9.9.9.9', $resolver->getNearestServerIp());
    }

    #[Test]
    public function getForwardedHostAndProto(): void
    {
        $resolver = $this->createResolver(
            httpConfig: ['trusted_headers' => ['x-forwarded-host', 'x-forwarded-proto']],
            headers: ['X-Forwarded-Host' => ['app.test'], 'X-Forwarded-Proto' => ['https']],
        );

        self::assertSame('app.test', $resolver->getForwardedHost());
        self::assertSame('https', $resolver->getForwardedProto());
    }

    #[Test]
    public function getRemoteAddrReturnsNullWhenMissing(): void
    {
        $resolver = $this->createResolver();

        self::assertNull($resolver->getRemoteAddr());
    }

    #[Test]
    public function getTrustedProxyIpReturnsRemoteAddrWhenNoProxiesConfigured(): void
    {
        $resolver = $this->createResolver(
            httpConfig: ['trusted_headers' => ['x-forwarded-for'], 'trusted_proxies' => []],
            serverParams: ['REMOTE_ADDR' => '9.9.9.9'],
        );

        self::assertSame('9.9.9.9', $resolver->getTrustedProxyIP());
    }

    #[Test]
    public function getTrustedProxyIpReturnsFirstUntrustedIpInChain(): void
    {
        $resolver = $this->createResolver(
            httpConfig: [
                'trusted_headers' => ['x-forwarded-for'],
                'trusted_proxies' => ['9.9.9.9'],
            ],
            serverParams: ['REMOTE_ADDR' => '9.9.9.9'],
            headers: ['X-Forwarded-For' => ['1.2.3.4']],
        );

        // Chain reversed is [remote_addr, forwarded_ip]; remote_addr (9.9.9.9) is trusted,
        // so the first untrusted ip found is the client ip.
        self::assertSame('1.2.3.4', $resolver->getTrustedProxyIP());
    }

    #[Test]
    public function getTrustedProxyIpFallsBackToRemoteAddrWhenAllIpsInChainAreTrusted(): void
    {
        $resolver = $this->createResolver(
            httpConfig: [
                'trusted_headers' => ['x-forwarded-for'],
                'trusted_proxies' => ['9.9.9.9'],
            ],
            serverParams: ['REMOTE_ADDR' => '9.9.9.9'],
            headers: ['X-Forwarded-For' => ['9.9.9.9']],
        );

        self::assertSame('9.9.9.9', $resolver->getTrustedProxyIP());
    }

    #[Test]
    public function isTrustedProxyComparesNearestClientIpWithTrustedProxyIp(): void
    {
        $resolver = $this->createResolver(
            httpConfig: [
                'trusted_headers' => ['x-forwarded-for'],
                'trusted_proxies' => ['9.9.9.9'],
            ],
            serverParams: ['REMOTE_ADDR' => '9.9.9.9'],
            headers: ['X-Forwarded-For' => ['1.2.3.4']],
        );

        self::assertTrue($resolver->isTrustedProxy());
    }

    #[Test]
    public function getTrustedProxiesReplacesRemoteAddrPlaceholderAndCachesResult(): void
    {
        $resolver = $this->createResolver(
            httpConfig: ['trusted_proxies' => 'REMOTE_ADDR,10.0.0.1'],
            serverParams: ['REMOTE_ADDR' => '9.9.9.9'],
        );

        $proxies = $resolver->getTrustedProxies();

        self::assertSame(['9.9.9.9', '10.0.0.1'], $proxies);
        self::assertSame($proxies, $resolver->getTrustedProxies());
    }

    #[Test]
    public function handleProxyHostRewritesSystemUriWhenTrustedProxyForwards(): void
    {
        $resolver = $this->createResolver(
            httpConfig: [
                'trusted_headers' => ['x-forwarded-for', 'x-forwarded-host', 'x-forwarded-proto'],
                'trusted_proxies' => ['9.9.9.9'],
            ],
            serverParams: ['REMOTE_ADDR' => '9.9.9.9'],
            headers: [
                'X-Forwarded-For' => ['1.2.3.4'],
                'X-Forwarded-Host' => ['app.example.com'],
                'X-Forwarded-Proto' => ['https'],
            ],
        );

        $uri = new SystemUri('http://internal.local/foo');
        $new = $resolver->handleProxyHost($uri);

        self::assertSame('app.example.com', $new->getHost());
        self::assertSame('https', $new->getScheme());
        self::assertSame('https://app.example.com/foo', $new->getOriginal());
    }

    #[Test]
    public function handleProxyHostReturnsUriUnchangedWhenNotProxied(): void
    {
        $resolver = $this->createResolver(
            httpConfig: ['trusted_headers' => []],
        );

        $uri = new SystemUri('http://internal.local/foo');
        $new = $resolver->handleProxyHost($uri);

        self::assertSame($uri, $new);
    }

    #[Test]
    public function headerPrefixGetterAndSetter(): void
    {
        $resolver = $this->createResolver();

        self::assertSame('x-', $resolver->getHeaderPrefix());

        $new = $resolver->setHeaderPrefix('custom-');

        self::assertSame($resolver, $new);
        self::assertSame('custom-', $resolver->getHeaderPrefix());
    }

    #[Test]
    public function handleTrustedProxiesExplodesStringAndReplacesRemoteAddrPlaceholder(): void
    {
        $result = ProxyResolver::handleTrustedProxies('REMOTE_ADDR, 10.0.0.1 ,10.0.0.2', '1.1.1.1');

        self::assertSame(['1.1.1.1', '10.0.0.1', '10.0.0.2'], $result);
    }

    #[Test]
    public function handleTrustedProxiesAcceptsArrayInput(): void
    {
        $result = ProxyResolver::handleTrustedProxies(
            [
                'REMOTE_ADDR',
                '10.0.0.0/8, 192.168.0.0/16',
                "172.16.0.0/12\n 192.0.2.0/24",
            ],
            '1.1.1.1',
        );

        self::assertSame(
            [
                '1.1.1.1',
                '10.0.0.0/8',
                '192.168.0.0/16',
                '172.16.0.0/12',
                '192.0.2.0/24',
            ],
            $result,
        );
    }

    #[Test]
    public function splitIpsFiltersOutNonIpValues(): void
    {
        $result = ProxyResolver::splitIps('1.2.3.4, not-an-ip , ::1');

        self::assertSame([0 => '1.2.3.4', 2 => '::1'], $result);
    }
}
