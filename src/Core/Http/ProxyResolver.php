<?php

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\Runtime\Config;
use Windwalker\Http\Helper\IpHelper;
use Windwalker\Uri\Uri;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

/**
 * The ProxyHandler class.
 */
class ProxyResolver
{
    use InstanceCacheTrait;

    protected string $headerPrefix = 'x-';

    /**
     * @var array
     */
    protected array $config;

    public function __construct(Config $config, protected ServerRequestInterface $request)
    {
        $this->config = (array) $config->getDeep('http');
    }

    public function getHeader(string $name): ?string
    {
        $name = $this->getHeaderPrefix() . $name;
        $name = strtolower($name);

        if (!in_array($name, $this->config['trusted_headers'] ?? [], true)) {
            return null;
        }

        return $this->request->getHeaderLine($name);
    }

    public function isProxy(): bool
    {
        return (bool) $this->getHeader('forwarded-for');
    }

    public function isTrustedProxy(): bool
    {
        return (bool) $this->getTrustedProxyIP();
    }

    public function getForwardedIP(): ?string
    {
        return $this->getHeader('forwarded-for');
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
        $ip = $this->getRemoteAddr();

        if (!$ip) {
            return null;
        }

        if (IpHelper::checkIp($ip, $trustedProxies)) {
            return $ip;
        }

        return null;
    }

    public function getTrustedProxies(): array
    {
        return $this->once(
            'trusted.proxies',
            function () {
                $trustedProxies = $this->config['trusted_proxies'] ?? '';

                return static::handleTrustedProxies($trustedProxies, $this->getRemoteAddr());
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
     * @return string
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
     * @param  mixed   $trustedProxies
     * @param  string  $remoteAddr
     *
     * @return array
     */
    public static function handleTrustedProxies(string|array $trustedProxies, string $remoteAddr = ''): array
    {
        if (is_string($trustedProxies)) {
            $trustedProxies = Arr::explodeAndClear(',', $trustedProxies);
        }

        foreach ($trustedProxies as &$trustedProxy) {
            if ($trustedProxy === 'REMOTE_ADDR' && $remoteAddr) {
                $trustedProxy = $remoteAddr;
            }
        }

        return $trustedProxies;
    }
}
