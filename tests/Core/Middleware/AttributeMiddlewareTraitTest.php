<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use Closure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Middleware\AttributeMiddlewareTrait;
use Windwalker\Http\Response\Response;

class AttributeMiddlewareTraitTest extends TestCase
{
    private function handler(): RequestHandlerInterface
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturnCallback(
            static fn(ServerRequestInterface $request): ResponseInterface
                => new Response()->withHeader(
                    'X-Handled',
                    '1',
                ),
        );

        return $handler;
    }

    #[Test]
    public function processCallsNextWhenRunDelegatesToNext(): void
    {
        $middleware = new class () {
            use AttributeMiddlewareTrait;

            public function run(ServerRequestInterface $request, Closure $next): mixed
            {
                return $next($request);
            }
        };

        $result = $middleware->process($this->createStub(ServerRequestInterface::class), $this->handler());

        self::assertSame('1', $result->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function processConvertsNonResponseReturnValueViaAnyToResponse(): void
    {
        $middleware = new class () {
            use AttributeMiddlewareTrait;

            public function run(ServerRequestInterface $request, Closure $next): mixed
            {
                return 'short-circuited';
            }
        };

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $result = $middleware->process($this->createStub(ServerRequestInterface::class), $handler);

        self::assertSame('short-circuited', (string) $result->getBody());
    }
}
