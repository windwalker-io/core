<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Middleware\SefMiddleware;
use Windwalker\Http\Response\Response;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;

class SefMiddlewareTest extends TestCase
{
    private function createContext(string $uri = 'http://example.com/foo'): AppContext
    {
        return TestWebAppFactory::create()->createContext(TestWebAppFactory::request($uri));
    }

    private function handlerWithBody(string $body): RequestHandlerInterface
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturnCallback(
            static function (ServerRequestInterface $request) use ($body): ResponseInterface {
                $response = new Response();
                $response->getBody()->write($body);

                return $response;
            },
        );

        return $handler;
    }

    #[Test]
    public function processDoesNothingWhenDisabled(): void
    {
        $ctx = $this->createContext();
        $html = '<a href="/foo">link</a>';
        $middleware = new SefMiddleware($ctx, enabled: false);

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertSame($html, (string) $result->getBody());
    }

    #[Test]
    public function processReplacesHrefAttributes(): void
    {
        $ctx = $this->createContext();
        $html = '<a href="page.html">link</a>';
        $middleware = new SefMiddleware($ctx, baseHref: '/base/', baseSrc: '/src/');

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertSame('<a href="/base/page.html">link</a>', (string) $result->getBody());
    }

    #[Test]
    public function processDoesNotReplaceAbsoluteOrProtocolHref(): void
    {
        $ctx = $this->createContext();
        $html = '<a href="/already-abs">a</a> <a href="https://example.org/x">b</a>';
        $middleware = new SefMiddleware($ctx, baseHref: '/base/', baseSrc: '/src/');

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertSame($html, (string) $result->getBody());
    }

    #[Test]
    public function processReplacesSrcAttributes(): void
    {
        $ctx = $this->createContext();
        $html = '<img src="pic.png">';
        $middleware = new SefMiddleware($ctx, baseHref: '/base/', baseSrc: '/src/');

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertSame('<img src="/base/pic.png">', (string) $result->getBody());
    }

    #[Test]
    public function processReplacesPosterAttribute(): void
    {
        $ctx = $this->createContext();
        $html = '<video poster="cover.jpg"></video>';
        $middleware = new SefMiddleware($ctx, baseHref: '/base/', baseSrc: '/src/');

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertSame('<video poster="/base/cover.jpg"></video>', (string) $result->getBody());
    }

    #[Test]
    public function processReplacesSrcsetAttributes(): void
    {
        $ctx = $this->createContext();
        $html = '<img srcset="small.jpg 1x, large.jpg 2x">';
        $middleware = new SefMiddleware($ctx, baseHref: '/base/', baseSrc: '/src/');

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertSame(
            '<img srcset="/src/small.jpg 1x, /src/large.jpg 2x">',
            (string) $result->getBody(),
        );
    }

    #[Test]
    public function processReplacesWindowOpenCalls(): void
    {
        $ctx = $this->createContext();
        $html = '<a onclick="window.open(\'page.html\')">open</a>';
        $middleware = new SefMiddleware($ctx, baseHref: '/base/', baseSrc: '/src/');

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertSame(
            '<a onclick="window.open(\'/base/page.html\')">open</a>',
            (string) $result->getBody(),
        );
    }

    #[Test]
    public function processReplacesInlineStyleUrls(): void
    {
        $ctx = $this->createContext();
        $html = '<div style="background:url(bg.png)"></div>';
        $middleware = new SefMiddleware($ctx, baseHref: '/base/', baseSrc: '/src/');

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertSame(
            '<div style="background: url(/src/bg.png)"></div>',
            (string) $result->getBody(),
        );
    }

    #[Test]
    public function processReplacesStyleTagUrls(): void
    {
        $ctx = $this->createContext();
        $html = '<style>.a { background: url(bg.png); }</style>';
        $middleware = new SefMiddleware($ctx, baseHref: '/base/', baseSrc: '/src/');

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertSame(
            '<style>.a { background: url(/src/bg.png); }</style>',
            (string) $result->getBody(),
        );
    }

    #[Test]
    public function processReplacesObjectParamTags(): void
    {
        $ctx = $this->createContext();
        $html = '<param name="movie" value="movie.swf">';
        $middleware = new SefMiddleware($ctx, baseHref: '/base/', baseSrc: '/src/');

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertSame(
            '<param name="movie" value="/src/movie.swf">',
            (string) $result->getBody(),
        );
    }

    #[Test]
    public function processReplacesObjectDataAttribute(): void
    {
        $ctx = $this->createContext();
        $html = '<object data="movie.swf"></object>';
        $middleware = new SefMiddleware($ctx, baseHref: '/base/', baseSrc: '/src/');

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertSame(
            '<object data="/src/movie.swf"></object>',
            (string) $result->getBody(),
        );
    }

    #[Test]
    public function processReplacesHashAnchorsUsingFullCurrentUri(): void
    {
        $ctx = $this->createContext('http://example.com/foo/bar?x=1');
        $html = '<a href="#section">jump</a>';
        $middleware = new SefMiddleware($ctx, baseHref: '/base/', baseSrc: '/src/');

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertStringContainsString('#section"', (string) $result->getBody());
        self::assertStringNotContainsString('href="#section"', (string) $result->getBody());
    }

    #[Test]
    public function processHonorsFlagsToSkipDisabledReplacements(): void
    {
        $ctx = $this->createContext();
        $html = '<a href="page.html">link</a><img src="pic.png">';
        $middleware = new SefMiddleware(
            $ctx,
            baseHref: '/base/',
            baseSrc: '/src/',
            flags: SefMiddleware::REPLACE_SRC,
        );

        $result = $middleware->process($ctx->getServerRequest(), $this->handlerWithBody($html));

        self::assertSame(
            '<a href="page.html">link</a><img src="/base/pic.png">',
            (string) $result->getBody(),
        );
    }
}
