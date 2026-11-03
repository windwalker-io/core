<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Middleware\ForceSslMiddleware;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Http\Response\Response;

class ForceSslMiddlewareTest extends TestCase
{
    private function handler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response()->withHeader('X-Handled', '1');
            }
        };
    }

    #[Test]
    public function disabledPassesThroughWithoutRedirect(): void
    {
        $middleware = new ForceSslMiddleware(enabled: false);

        $response = $middleware->process(new ServerRequest(uri: 'http://example.com/foo'), $this->handler());

        self::assertSame('1', $response->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function alreadyHttpsPassesThroughWithoutRedirect(): void
    {
        $middleware = new ForceSslMiddleware();

        $response = $middleware->process(new ServerRequest(uri: 'https://example.com/foo'), $this->handler());

        self::assertSame('1', $response->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function httpRequestRedirectsToHttpsWithDefaultCode(): void
    {
        $middleware = new ForceSslMiddleware();

        $response = $middleware->process(new ServerRequest(uri: 'http://example.com/foo?x=1'), $this->handler());

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('https://example.com/foo?x=1', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function invalidRedirectCodeIsNormalizedToDefault(): void
    {
        $middleware = new ForceSslMiddleware(redirectCode: 999);

        $response = $middleware->process(new ServerRequest(uri: 'http://example.com/foo'), $this->handler());

        self::assertSame(303, $response->getStatusCode());
    }

    #[Test]
    public function validRedirectCodeIsKept(): void
    {
        $middleware = new ForceSslMiddleware(redirectCode: 301);

        $response = $middleware->process(new ServerRequest(uri: 'http://example.com/foo'), $this->handler());

        self::assertSame(301, $response->getStatusCode());
    }

    #[Test]
    public function arrayHeadersAreAddedToRedirectResponse(): void
    {
        $middleware = new ForceSslMiddleware(headers: ['X-Foo' => 'bar']);

        $response = $middleware->process(new ServerRequest(uri: 'http://example.com/foo'), $this->handler());

        self::assertSame('bar', $response->getHeaderLine('X-Foo'));
    }

    #[Test]
    public function callableHeadersCanRebuildResponse(): void
    {
        $middleware = new ForceSslMiddleware(
            headers: static fn(ResponseInterface $res, ServerRequestInterface $req) => $res->withHeader(
                'X-Path',
                $req->getUri()->getPath()
            )
        );

        $response = $middleware->process(new ServerRequest(uri: 'http://example.com/foo'), $this->handler());

        self::assertSame('/foo', $response->getHeaderLine('X-Path'));
    }
}
