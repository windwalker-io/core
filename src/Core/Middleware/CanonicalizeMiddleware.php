<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Middleware\Enum\WwwRedirect;
use Windwalker\Filter\Rule\IPV4;
use Windwalker\Filter\Rule\IPV6;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Utilities\Str;

class CanonicalizeMiddleware implements MiddlewareInterface
{
    // phpcs:disable
    protected \Closure|WwwRedirect $wwwRedirect {
        set(\Closure|WwwRedirect|int|string $value) {
            if ($value instanceof \Closure || $value instanceof WwwRedirect) {
                $this->wwwRedirect = $value;
            } else {
                $this->wwwRedirect = WwwRedirect::from((int) $value);
            }
        }
    }
    // phpcs:enable

    public function __construct(
        protected AppContext $app,
        protected \Closure|bool $forceSsl = true,
        \Closure|WwwRedirect|int|string $wwwRedirect = WwwRedirect::NO_ACTIONS,
        protected \Closure|int $redirectType = 307,
        protected \DateInterval|string|int|bool $hsts = false,
    ) {
        $this->wwwRedirect = $wwwRedirect;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();

        if ($uri->getScheme() === 'http' && $this->isForceSslEnabled($request)) {
            $uri = $uri->withScheme('https');

            if (is_int($this->redirectType)) {
                $res = new RedirectResponse(
                    $uri,
                    $this->normalizeCode($this->redirectType)
                );
            } else {
                $res = $this->app->call($this->redirectType);
            }

            if ($this->hsts && !$res->getHeader('Strict-Transport-Security')) {
                if (is_string($this->hsts)) {
                    $hsts = $this->hsts;
                } elseif (is_int($this->hsts)) {
                    $hsts = 'max-age=' . $this->hsts . '; includeSubDomains';
                } elseif ($this->hsts instanceof \DateInterval) {
                    $hsts = 'max-age=' . Chronos::intervalToSeconds($this->hsts) . '; includeSubDomains';
                } else {
                    $hsts = 'max-age=31536000; includeSubDomains';
                }

                $res = $res->withHeader('Strict-Transport-Security', $hsts);
            }

            return $res;
        }

        $wwwRedirect = $this->getWwwRedirectAction($request);

        if ($wwwRedirect !== WwwRedirect::NO_ACTIONS) {
            $host = $uri->getHost();

            if ($wwwRedirect === WwwRedirect::WWW) {
                // Non-www to www
                if (str_starts_with($host, 'www.') || self::isIgnoredHost($host)) {
                    return $handler->handle($request);
                }

                $uri = $uri->withHost('www.' . $host);
            } else {
                // www to non-www
                if (!str_starts_with($host, 'www.') || self::isIgnoredHost($host)) {
                    return $handler->handle($request);
                }

                $uri = $uri->withHost(Str::removeStart($host, 'www.'));
            }

            if (is_int($this->redirectType)) {
                $res = new RedirectResponse(
                    $uri,
                    $this->normalizeCode($this->redirectType)
                );
            } else {
                $res = $this->app->call($this->redirectType);
            }

            return $res;
        }

        return $handler->handle($request);
    }

    protected function normalizeCode(int $code): int
    {
        if ($code < 300 || $code >= 400) {
            $code = 307;
        }

        return $code;
    }

    public function isForceSslEnabled(ServerRequestInterface $request): bool
    {
        $enabled = $this->forceSsl;

        if ($enabled instanceof \Closure) {
            $enabled = (bool) $this->app->call(
                $enabled,
                [
                    ServerRequestInterface::class => $request,
                    'request' => $request,
                ]
            );
        }

        return $enabled;
    }

    public function getWwwRedirectAction(ServerRequestInterface $request): WwwRedirect
    {
        $wwwRedirect = $this->wwwRedirect;

        if ($wwwRedirect instanceof \Closure) {
            $wwwRedirect = $this->app->call(
                $wwwRedirect,
                [
                    ServerRequestInterface::class => $request,
                    'request' => $request,
                ]
            );

            if (!$wwwRedirect instanceof WwwRedirect) {
                $wwwRedirect = WwwRedirect::from($wwwRedirect);
            }
        }

        return $wwwRedirect;
    }

    protected static function isIgnoredHost(string $host): bool
    {
        if (self::isIp($host)) {
            return true;
        }

        if ($host === 'localhost' || $host === '127.0.0.1') {
            return true;
        }

        return false;
    }

    protected static function isIp(string $host): bool
    {
        if (new IPV4()->test($host)) {
            return true;
        }

        if (new IPV6()->test($host)) {
            return true;
        }

        return false;
    }
}
