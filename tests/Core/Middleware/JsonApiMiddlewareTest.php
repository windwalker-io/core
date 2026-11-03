<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Windwalker\Core\Http\Exception\ApiException;
use Windwalker\Core\Middleware\JsonApiMiddleware;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\DIOptions;
use Windwalker\Http\Response\JsonResponse;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Http\Response\Response;
use Windwalker\Session\Bridge\PhpBridge;
use Windwalker\Session\Handler\ArrayHandler;
use Windwalker\Session\Session;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;

class JsonApiMiddlewareTest extends TestCase
{
    private function createMiddleware(array $config = []): JsonApiMiddleware
    {
        $app = TestWebAppFactory::create($config);
        $ctx = $app->createContext(TestWebAppFactory::request());
        $ctx->getContainer()->share(
            Session::class,
            new Session([], new PhpBridge(handler: new ArrayHandler()))
        );
        $app->getContainer()->set(
            LoggerInterface::class,
            new NullLogger(),
            new DIOptions(shared: true),
            tag: 'error',
        );
        $ctx->getContainer()->share(
            ErrorService::class,
            new ErrorService($ctx->config, $app)
        );

        return new JsonApiMiddleware($ctx);
    }

    #[Test]
    public function redirectResponseIsReturnedUnchanged(): void
    {
        $middleware = $this->createMiddleware();
        $redirect = new RedirectResponse('http://example.com/target');

        $result = $middleware->run(TestWebAppFactory::request(), static fn() => $redirect);

        self::assertSame($redirect, $result);
    }

    #[Test]
    public function responseWithLocationHeaderIsReturnedUnchanged(): void
    {
        $middleware = $this->createMiddleware();
        $response = new Response()->withHeader('Location', 'http://example.com/target');

        $result = $middleware->run(TestWebAppFactory::request(), static fn() => $response);

        self::assertSame($response, $result);
    }

    #[Test]
    public function responseWithNonJsonContentTypeIsReturnedUnchanged(): void
    {
        $middleware = $this->createMiddleware();
        $response = new Response()->withHeader('Content-Type', 'text/html');

        $result = $middleware->run(TestWebAppFactory::request(), static fn() => $response);

        self::assertSame($response, $result);
    }

    #[Test]
    public function jsonResponseIsWrappedInJsonBuffer(): void
    {
        $middleware = $this->createMiddleware();
        $response = new JsonResponse(['foo' => 'bar'], 201);

        $result = $middleware->run(TestWebAppFactory::request(), static fn() => $response);

        self::assertInstanceOf(JsonResponse::class, $result);
        self::assertSame(201, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        self::assertSame(201, $body['status']);
        self::assertSame('bar', $body['data']['foo']);
    }

    #[Test]
    public function plainResponseBodyIsDecodedAsJson(): void
    {
        $middleware = $this->createMiddleware();
        $response = new Response();
        $response->getBody()->write('{"foo":"bar"}');

        $result = $middleware->run(TestWebAppFactory::request(), static fn() => $response);

        self::assertInstanceOf(JsonResponse::class, $result);
        $body = json_decode((string) $result->getBody(), true);
        self::assertSame('bar', $body['data']->foo ?? $body['data']['foo']);
    }

    #[Test]
    public function nonResponseResultIsWrappedInJsonBuffer(): void
    {
        $middleware = $this->createMiddleware();

        $result = $middleware->run(TestWebAppFactory::request(), static fn() => ['foo' => 'bar']);

        self::assertInstanceOf(JsonResponse::class, $result);
        self::assertSame(200, $result->getStatusCode());
        $body = json_decode((string) $result->getBody(), true);
        self::assertSame('bar', $body['data']['foo']);
    }

    #[Test]
    public function exceptionIsHandledAsApiErrorNonDebug(): void
    {
        $middleware = $this->createMiddleware(['debug' => false]);

        $result = $middleware->run(
            TestWebAppFactory::request(),
            static function (): never {
                throw new RuntimeException('boom', 500);
            }
        );

        self::assertInstanceOf(JsonResponse::class, $result);
        $body = json_decode((string) $result->getBody(), true);
        self::assertFalse($body['success']);
        self::assertSame('boom', $body['message']);
        self::assertArrayNotHasKey('exception', $body['data']);
    }

    #[Test]
    public function exceptionIsHandledAsApiErrorDebug(): void
    {
        $middleware = $this->createMiddleware(['debug' => true]);

        $result = $middleware->run(
            TestWebAppFactory::request(),
            static function (): never {
                throw new RuntimeException('boom', 500);
            }
        );

        self::assertInstanceOf(JsonResponse::class, $result);
        $body = json_decode((string) $result->getBody(), true);
        self::assertStringContainsString('File:', $body['message']);
        self::assertSame(RuntimeException::class, $body['data']['exception']);
        self::assertArrayHasKey('backtraces', $body['data']);
    }

    #[Test]
    public function apiExceptionStatusCodeIsUsedForResponse(): void
    {
        $middleware = $this->createMiddleware();

        $result = $middleware->run(
            TestWebAppFactory::request(),
            static function (): never {
                throw new ApiException('not found', 0, 404);
            }
        );

        self::assertInstanceOf(JsonResponse::class, $result);
        self::assertSame(404, $result->getStatusCode());
    }
}
