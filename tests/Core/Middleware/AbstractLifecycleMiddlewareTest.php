<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Middleware\AbstractLifecycleMiddleware;
use Windwalker\Http\Response\Response;

class AbstractLifecycleMiddlewareTest extends TestCase
{
    private function handler(): RequestHandlerInterface
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturnCallback(
            static fn(ServerRequestInterface $request): ResponseInterface
                => new Response()->withHeader(
                    'X-Request-Path',
                    $request->getUri()->getPath(),
                ),
        );

        return $handler;
    }

    #[Test]
    public function processCallsPreprocessThenHandlerThenPostProcess(): void
    {
        $middleware = new class () extends AbstractLifecycleMiddleware {
            public array $calls = [];

            protected function preprocess(ServerRequestInterface $request)
            {
                $this->calls[] = 'pre';

                return null;
            }

            protected function postProcess(ResponseInterface $response)
            {
                $this->calls[] = 'post';

                return $response->withHeader('X-Post', '1');
            }
        };

        $request = $this->createStub(ServerRequestInterface::class);
        $uri = $this->createStub(\Psr\Http\Message\UriInterface::class);
        $uri->method('getPath')->willReturn('/foo');
        $request->method('getUri')->willReturn($uri);

        $result = $middleware->process($request, $this->handler());

        self::assertSame(['pre', 'post'], $middleware->calls);
        self::assertSame('/foo', $result->getHeaderLine('X-Request-Path'));
        self::assertSame('1', $result->getHeaderLine('X-Post'));
    }

    #[Test]
    public function processReturnsEarlyWhenPreprocessReturnsResponse(): void
    {
        $shortCircuit = new Response()->withHeader('X-Short', '1');

        $middleware = new class ($shortCircuit) extends AbstractLifecycleMiddleware {
            public bool $postCalled = false;

            public function __construct(private readonly ResponseInterface $shortCircuit) {}

            protected function preprocess(ServerRequestInterface $request)
            {
                return $this->shortCircuit;
            }

            protected function postProcess(ResponseInterface $response)
            {
                $this->postCalled = true;

                return null;
            }
        };

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $result = $middleware->process($this->createStub(ServerRequestInterface::class), $handler);

        self::assertSame('1', $result->getHeaderLine('X-Short'));
        self::assertFalse($middleware->postCalled);
    }

    #[Test]
    public function preprocessCanReplaceTheRequestPassedToHandler(): void
    {
        $replacement = $this->createStub(ServerRequestInterface::class);
        $replacementUri = $this->createStub(\Psr\Http\Message\UriInterface::class);
        $replacementUri->method('getPath')->willReturn('/replaced');
        $replacement->method('getUri')->willReturn($replacementUri);

        $middleware = new class ($replacement) extends AbstractLifecycleMiddleware {
            public function __construct(private readonly ServerRequestInterface $replacement) {}

            protected function preprocess(ServerRequestInterface $request)
            {
                return $this->replacement;
            }

            protected function postProcess(ResponseInterface $response)
            {
                return null;
            }
        };

        $result = $middleware->process($this->createStub(ServerRequestInterface::class), $this->handler());

        self::assertSame('/replaced', $result->getHeaderLine('X-Request-Path'));
    }

    #[Test]
    public function processKeepsOriginalResponseWhenPostProcessReturnsNull(): void
    {
        $middleware = new class () extends AbstractLifecycleMiddleware {
            protected function preprocess(ServerRequestInterface $request)
            {
                return null;
            }

            protected function postProcess(ResponseInterface $response)
            {
                return null;
            }
        };

        $request = $this->createStub(ServerRequestInterface::class);
        $uri = $this->createStub(\Psr\Http\Message\UriInterface::class);
        $uri->method('getPath')->willReturn('/bar');
        $request->method('getUri')->willReturn($uri);

        $result = $middleware->process($request, $this->handler());

        self::assertSame('/bar', $result->getHeaderLine('X-Request-Path'));
    }
}
