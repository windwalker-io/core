<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Middleware\CanonicalizeMiddleware;
use Windwalker\Core\Middleware\Enum\WwwRedirect;
use Windwalker\Http\Response\Response;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;

class CanonicalizeMiddlewareTest extends TestCase
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
    public function httpsRequestWithNoActionsPassesThrough(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('https://example.com/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, forceSsl: false);

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('1', $response->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function httpRequestRedirectsToHttpsWithDefaultCode(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('http://example.com/foo?x=1'));

        $middleware = new CanonicalizeMiddleware($ctx);

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame(307, $response->getStatusCode());
        self::assertSame('https://example.com/foo?x=1', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function invalidRedirectTypeCodeIsNormalized(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('http://example.com/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, redirectType: 999);

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame(307, $response->getStatusCode());
    }

    #[Test]
    public function forceSslClosureIsCalledWithRequest(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('http://example.com/foo'));

        $middleware = new CanonicalizeMiddleware(
            $ctx,
            forceSsl: static fn(ServerRequestInterface $request) => $request->getUri()->getPath() === '/foo'
        );

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame(307, $response->getStatusCode());
    }

    #[Test]
    public function redirectTypeClosureIsCalledByApp(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('http://example.com/foo'));

        $middleware = new CanonicalizeMiddleware(
            $ctx,
            redirectType: static fn() => new Response()->withHeader('X-Custom', 'redirect')
        );

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('redirect', $response->getHeaderLine('X-Custom'));
    }

    #[Test]
    public function hstsStringIsAppliedAsHeader(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('http://example.com/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, hsts: 'max-age=100');

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('max-age=100', $response->getHeaderLine('Strict-Transport-Security'));
    }

    #[Test]
    public function hstsIntSecondsIsConvertedToHeader(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('http://example.com/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, hsts: 3600);

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('max-age=3600; includeSubDomains', $response->getHeaderLine('Strict-Transport-Security'));
    }

    #[Test]
    public function hstsDateIntervalIsConvertedToSeconds(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('http://example.com/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, hsts: new \DateInterval('PT1H'));

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('max-age=3600; includeSubDomains', $response->getHeaderLine('Strict-Transport-Security'));
    }

    #[Test]
    public function hstsTrueUsesDefaultValue(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('http://example.com/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, hsts: true);

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('max-age=31536000; includeSubDomains', $response->getHeaderLine('Strict-Transport-Security'));
    }

    #[Test]
    public function hstsDoesNotOverrideExistingHeaderFromCustomRedirect(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('http://example.com/foo'));

        $middleware = new CanonicalizeMiddleware(
            $ctx,
            redirectType: static fn() => new Response()->withHeader('Strict-Transport-Security', 'already-set'),
            hsts: true
        );

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('already-set', $response->getHeaderLine('Strict-Transport-Security'));
    }

    #[Test]
    public function nonWwwToWwwRedirect(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('https://example.com/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, forceSsl: false, wwwRedirect: WwwRedirect::WWW);

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame(307, $response->getStatusCode());
        self::assertSame('https://www.example.com/foo', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function alreadyWwwPassesThroughForWwwRedirect(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('https://www.example.com/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, forceSsl: false, wwwRedirect: WwwRedirect::WWW);

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('1', $response->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function ignoredHostPassesThroughForWwwRedirect(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('https://localhost/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, forceSsl: false, wwwRedirect: WwwRedirect::WWW);

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('1', $response->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function ipHostIsIgnoredForWwwRedirect(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('https://127.0.0.1/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, forceSsl: false, wwwRedirect: WwwRedirect::WWW);

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('1', $response->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function wwwToNonWwwRedirect(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('https://www.example.com/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, forceSsl: false, wwwRedirect: WwwRedirect::NON_WWW);

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame(307, $response->getStatusCode());
        self::assertSame('https://example.com/foo', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function alreadyNonWwwPassesThroughForNoWwwRedirect(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('https://example.com/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, forceSsl: false, wwwRedirect: WwwRedirect::NON_WWW);

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('1', $response->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function wwwRedirectClosureIsResolvedByApp(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('https://example.com/foo'));

        $middleware = new CanonicalizeMiddleware(
            $ctx,
            forceSsl: false,
            wwwRedirect: static fn() => WwwRedirect::WWW
        );

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame(307, $response->getStatusCode());
        self::assertSame('https://www.example.com/foo', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function wwwRedirectClosureReturningIntIsConverted(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('https://example.com/foo'));

        $middleware = new CanonicalizeMiddleware(
            $ctx,
            forceSsl: false,
            wwwRedirect: static fn() => WwwRedirect::WWW->value
        );

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame(307, $response->getStatusCode());
    }

    #[Test]
    public function noActionsPassesThroughToHandler(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('https://example.com/foo'));

        $middleware = new CanonicalizeMiddleware($ctx, forceSsl: false);

        $response = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('1', $response->getHeaderLine('X-Handled'));
    }
}
