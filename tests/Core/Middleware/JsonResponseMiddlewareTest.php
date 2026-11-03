<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Windwalker\Core\Middleware\JsonResponseMiddleware;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\DIOptions;
use Windwalker\Http\Response\JsonResponse;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Http\Response\Response;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class JsonResponseMiddlewareTest extends TestCase
{
    private function createMiddleware(array $config = []): JsonResponseMiddleware
    {
        $app = TestWebAppFactory::create($config);
        $ctx = $app->createContext(TestWebAppFactory::request());
        $ctx->getContainer()->share(
            ErrorService::class,
            new ErrorService($ctx->config, $app)
        );
        $app->getContainer()->set(
            LoggerInterface::class,
            new NullLogger(),
            new DIOptions(shared: true),
            tag: 'error',
        );

        return new JsonResponseMiddleware($ctx);
    }

    #[Test]
    public function redirectResponseIsReturnedUnchanged(): void
    {
        $middleware = $this->createMiddleware();
        $redirect = new RedirectResponse('http://example.com/target');

        $result = $middleware->run(
            $this->req(),
            static fn(ServerRequestInterface $request) => $redirect,
        );

        self::assertSame($redirect, $result);
    }

    #[Test]
    public function responseWithLocationHeaderIsReturnedUnchanged(): void
    {
        $middleware = $this->createMiddleware();
        $response = new Response()->withHeader('Location', 'http://example.com/target');

        $result = $middleware->run(
            $this->req(),
            static fn(ServerRequestInterface $request) => $response,
        );

        self::assertSame($response, $result);
    }

    #[Test]
    public function responseWithNonJsonContentTypeIsReturnedUnchanged(): void
    {
        $middleware = $this->createMiddleware();
        $response = new Response()->withHeader('Content-Type', 'text/html');

        $result = $middleware->run(
            $this->req(),
            static fn(ServerRequestInterface $request) => $response,
        );

        self::assertSame($response, $result);
    }

    #[Test]
    public function plainResponseIsConvertedToJsonResponse(): void
    {
        $middleware = $this->createMiddleware();
        $response = new Response();
        $response->getBody()->write('{"foo":"bar"}');

        $result = $middleware->run(
            $this->req(),
            static fn(ServerRequestInterface $request) => $response,
        );

        self::assertInstanceOf(JsonResponse::class, $result);
    }

    #[Test]
    public function nonResponseResultIsWrappedInJsonResponse(): void
    {
        $middleware = $this->createMiddleware();

        $result = $middleware->run(
            $this->req(),
            static fn(ServerRequestInterface $request) => ['foo' => 'bar'],
        );

        self::assertInstanceOf(JsonResponse::class, $result);
        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('"foo":"bar"', (string) $result->getBody());
    }

    #[Test]
    public function exceptionIsCaughtAndReturnedAsJsonErrorResponseNonDebug(): void
    {
        $middleware = $this->createMiddleware(['debug' => false]);

        $result = $middleware->run(
            $this->req(),
            static function (ServerRequestInterface $request): never {
                throw new RuntimeException('boom', 500);
            },
        );

        self::assertInstanceOf(JsonResponse::class, $result);
        $body = (string) $result->getBody();
        self::assertStringContainsString('"error":"boom"', $body);
        self::assertStringNotContainsString('File:', $body);
    }

    #[Test]
    public function exceptionIsCaughtAndReturnedAsJsonErrorResponseDebug(): void
    {
        $middleware = $this->createMiddleware(['debug' => true]);

        $result = $middleware->run(
            $this->req(),
            static function (ServerRequestInterface $request): never {
                throw new RuntimeException('boom', 500);
            },
        );

        self::assertInstanceOf(JsonResponse::class, $result);
        $body = (string) $result->getBody();
        self::assertStringContainsString('File:', $body);
    }

    private function req(): ServerRequestInterface
    {
        return TestWebAppFactory::request();
    }
}
