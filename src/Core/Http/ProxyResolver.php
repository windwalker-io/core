<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2022 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Attributes\Ref;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Http\Helper\IpHelper;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

/**
 * The ProxyHandler class.
 */
class ProxyResolver
{
    use InstanceCacheTrait;

    public function __construct(#[Ref('http')] protected array $config, protected ServerRequestInterface $request)
    {
    }

    public function isProxy(): bool
    {
        return (bool) $this->request->getHeaderLine('x-forwarded-for');
    }

    public function isTrustedProxy(): bool
    {
        return (bool) $this->getTrustedProxyIP();
    }

    public function getForwardedIP(): ?string
    {
        return $this->request->getHeaderLine('x-forwarded-for');
    }

    public function getForwardedHost(): ?string
    {
        return $this->request->getHeaderLine('x-forwarded-host');
    }

    public function getForwardedProto(): ?string
    {
        return $this->request->getHeaderLine('x-forwarded-proto');
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

                if (is_string($trustedProxies)) {
                    $trustedProxies = Arr::explodeAndClear(',', $trustedProxies);
                }

                foreach ($trustedProxies as &$trustedProxy) {
                    if ($trustedProxy === 'REMOTE_ADDR') {
                        $trustedProxy = $this->getRemoteAddr();
                    }
                }

                return $trustedProxies;
            }
        );
    }

    public function handleProxyHost(SystemUri $uri): SystemUri
    {
        if ($this->isTrustedProxy()) {
            $uri = $uri->withHost($this->getForwardedHost());
            $uri = $uri->withScheme($this->getForwardedProto());
        }

        return $uri;
    }
}
