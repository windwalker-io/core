<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\MiddlewareRunner;
use Windwalker\DI\Container;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Http\Response\Response;
use Windwalker\Tests\Core\Application\Stub\PassThroughMiddleware;
use Windwalker\Utilities\Wrapper\RawWrapper;

class MiddlewareRunnerTest extends TestCase
{
    protected MiddlewareRunner $instance;

    #[Before]
    protected function setUpInstance(): void
    {
        $this->instance = new MiddlewareRunner(new Container());
    }

    private function tagger(string $tag): callable
    {
        return static fn(ServerRequestInterface $req, RequestHandlerInterface $next): ResponseInterface => $next
            ->handle($req->withAttribute('trace', ($req->getAttribute('trace') ?? '') . $tag));
    }

    #[Test]
    public function chainMiddlewaresWithoutLast(): void
    {
        $a = fn() => 1;
        $b = fn() => 2;

        self::assertSame([$a, $b], iterator_to_array(MiddlewareRunner::chainMiddlewares([$a, $b]), false));
    }

    #[Test]
    public function chainMiddlewaresAppendsLastWrapper(): void
    {
        $last = fn() => 'last';

        $items = iterator_to_array(MiddlewareRunner::chainMiddlewares([], $last), false);

        self::assertCount(1, $items);
        self::assertSame($last, $items[0]());
    }

    #[Test]
    public function resolveMiddlewareFalsyReturnsNull(): void
    {
        self::assertNull($this->instance->resolveMiddleware(false));
        self::assertNull($this->instance->resolveMiddleware(null));
    }

    #[Test]
    public function resolveMiddlewareRawWrapperUnwraps(): void
    {
        $fn = fn() => 'raw';

        self::assertSame($fn, $this->instance->resolveMiddleware(new RawWrapper($fn)));
    }

    #[Test]
    public function resolveMiddlewareCallableReturningCallableIsUsed(): void
    {
        $inner = fn() => 'inner';

        self::assertSame($inner, $this->instance->resolveMiddleware(static fn() => $inner));
    }

    #[Test]
    public function resolveMiddlewareClassNameIsResolvedByContainer(): void
    {
        $resolved = $this->instance->resolveMiddleware(PassThroughMiddleware::class);

        self::assertInstanceOf(PassThroughMiddleware::class, $resolved);
    }

    #[Test]
    public function compileMiddlewares(): void
    {
        $last = fn() => 'last';

        $items = iterator_to_array(
            $this->instance->compileMiddlewares([false, new RawWrapper($last)], $last),
            false
        );

        self::assertSame([null, $last, $last], $items);
    }

    #[Test]
    public function runExecutesInOrderAndEndsWithLast(): void
    {
        $response = $this->instance->run(
            new ServerRequest(),
            [
                new RawWrapper($this->tagger('A')),
                new RawWrapper($this->tagger('B')),
            ],
            static fn(ServerRequestInterface $req) => new Response()
                ->withHeader('X-Trace', $req->getAttribute('trace'))
        );

        self::assertSame('AB', $response->getHeaderLine('X-Trace'));
    }

    #[Test]
    public function runSupportsClassMiddleware(): void
    {
        $response = $this->instance->run(
            new ServerRequest(),
            [PassThroughMiddleware::class],
            static fn(ServerRequestInterface $req) => new Response()
                ->withHeader('X-Trace', (string) $req->getAttribute('passed'))
        );

        self::assertSame('yes', $response->getHeaderLine('X-Trace'));
    }

    #[Test]
    public function middlewareCanShortCircuit(): void
    {
        $stop = static fn() => new Response()->withHeader('X-Stop', '1');

        $response = $this->instance->run(
            new ServerRequest(),
            [new RawWrapper($stop)],
            static fn() => new Response()->withHeader('X-Stop', '0')
        );

        self::assertSame('1', $response->getHeaderLine('X-Stop'));
    }
}
