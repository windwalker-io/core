<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Middleware\SimpleCspMiddleware;
use Windwalker\Core\Security\CspNonceService;
use Windwalker\DI\Container;

/**
 * `paragonie/csp-builder` is not installed in this package's dev dependencies,
 * so `process()` always throws before reaching any CSP-building logic. That is
 * the only branch exercisable in this environment; the enabled/disabled CSP
 * building logic requires that optional dependency to be present.
 */
class SimpleCspMiddlewareTest extends TestCase
{
    #[Test]
    public function processThrowsWhenCspBuilderPackageIsMissing(): void
    {
        $middleware = new SimpleCspMiddleware(
            $this->createStub(AssetService::class),
            $this->createStub(CspNonceService::class),
            new Container(),
        );

        $handler = $this->createStub(RequestHandlerInterface::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Please install `paragonie/csp-builder` first.');

        $middleware->process($this->createStub(ServerRequestInterface::class), $handler);
    }
}
