<?php

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Http\Helper\IpHelper;
use Windwalker\Uri\Uri;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

use function Windwalker\collect;

/**
 * The ProxyHandler class.
 */
class ProxyResolver
{
    use InstanceCacheTrait;

    protected string $headerPrefix = 'x-';

    /**
     * @param  (Closure(ServerRequestInterface): bool)|null  $trustedProxyCallback
     */
    public function __construct(
        protected ServerRequestInterface $request,
        protected array $trustedHeaders = [],
        protected string|array $trustedProxies = [],
        protected ?Closure $trustedProxyCallback = null,
    ) {}

    public function getHeader(string $name): ?string
    {
        $name = $this->getHeaderPrefix() . $name;
        $name = strtolower($name);

        if (!in_array($name, $this->trustedHeaders, true)) {
            return null;
        }

        return $this->request->getHeader($name)[0] ?? null;
    }

    public function isProxy(): bool
    {
        return (bool) $this->getHeader('forwarded-for');
    }

    public function isTrustedProxy(): bool
    {
        return $this->getNearestClientIp() === $this->getTrustedProxyIP();
    }

    public function getForwardedIP(): ?string
    {
        $ips = $this->getForwardedIPs();

        return array_shift($ips);
    }

    public function getForwardedIPs(): array
    {
        $header = $this->getHeader('forwarded-for');

        if (!$header) {
            return [];
        }

        return static::splitIps($header);
    }

    /**
     * If ips like:
     *
     * ```
     * X-Forwarded-For: client, proxy1, proxy2
     * REMOTE_ADDR: proxy3
     * ```
     *
     * Will return [client, proxy1, proxy2, proxy3 (remote_addr)]
     *
     * @return  array
     */
    public function getIpsChain(): array
    {
        $forwards = $this->getForwardedIPs();

        return [
            ...$forwards,
            $this->getRemoteAddr()
        ];
    }

    public function getNearestServerIp(): ?string
    {
        $proxyIps = $this->getIpsChain();

        return array_pop($proxyIps);
    }

    public function getNearestClientIp(): ?string
    {
        $proxyIps = $this->getIpsChain();

        return array_shift($proxyIps);
    }

    public function getForwardedHost(): ?string
    {
        return $this->getHeader('forwarded-host');
    }

    public function getForwardedProto(): ?string
    {
        return $this->getHeader('forwarded-proto');
    }

    public function getRemoteAddr(): ?string
    {
        return $this->request->getServerParams()['REMOTE_ADDR'] ?? null;
    }

    public function getTrustedProxyIP(): ?string
    {
        $trustedProxies = $this->getTrustedProxies();

        $proxyIps = $this->getIpsChain();

        $proxyIps = array_reverse($proxyIps);

        foreach ($proxyIps as $proxyIp) {
            if (!IpHelper::checkIp($proxyIp, $trustedProxies)) {
                return $proxyIp;
            }
        }

        return $this->getRemoteAddr();
    }

    public function getTrustedProxies(): array
    {
        return $this->once(
            'trusted.proxies',
            function () {
                $remoteAddr = $this->getRemoteAddr();
                $trustedProxies = static::handleTrustedProxies($this->trustedProxies, $remoteAddr ?? '');

                if (
                    $remoteAddr
                    && $this->trustedProxyCallback !== null
                    && ($this->trustedProxyCallback)($this->request) === true
                    && !IpHelper::checkIp($remoteAddr, $trustedProxies)
                ) {
                    $trustedProxies[] = $remoteAddr;
                }

                return $trustedProxies;
            }
        );
    }

    public function handleProxyHost(SystemUri $uri): SystemUri
    {
        if ($this->isProxy() && $this->isTrustedProxy()) {
            if ($this->getForwardedHost()) {
                $uri = $uri->withHost($this->getForwardedHost());
            }

            if ($this->getForwardedProto()) {
                $uri = $uri->withScheme($this->getForwardedProto());
            }

            $origin = Uri::wrap($uri->getOriginal());
            $origin = $origin->withHost($uri->getHost());
            $origin = $origin->withScheme($uri->getScheme());

            $uri = $uri->withOriginal((string) $origin);
        }

        return $uri;
    }

    /**
     * @return  string
     */
    public function getHeaderPrefix(): string
    {
        return $this->headerPrefix;
    }

    /**
     * @param  string  $headerPrefix
     *
     * @return  static  Return self to support chaining.
     */
    public function setHeaderPrefix(string $headerPrefix): static
    {
        $this->headerPrefix = $headerPrefix;

        return $this;
    }

    /**
     * @param  string|array  $trustedProxies
     * @param  string        $remoteAddr
     *
     * @return  array
     */
    public static function handleTrustedProxies(string|array $trustedProxies, string $remoteAddr = ''): array
    {
        $trustedProxies = collect((array) $trustedProxies)
            ->map(fn($v) => preg_split('/[\r\n,]+/', $v))
            ->collapse(false)
            ->map('trim')
            ->filter()
            ->dump();

        foreach ($trustedProxies as &$trustedProxy) {
            if ($trustedProxy === 'REMOTE_ADDR' && $remoteAddr) {
                $trustedProxy = $remoteAddr;
            }
        }

        return $trustedProxies;
    }

    public static function splitIps(string $ipString): array
    {
        $ips = Arr::explodeAndClear(
            ',',
            $ipString
        );

        return array_filter($ips, static fn($ip) => IpHelper::isIp($ip));
    }
}
