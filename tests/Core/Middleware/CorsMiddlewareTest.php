<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Http\CorsHandler;
use Windwalker\Core\Middleware\CorsMiddleware;
use Windwalker\DI\Container;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Http\Response\Response;

class CorsMiddlewareTest extends TestCase
{
    private function handler(?ResponseInterface $response = null): RequestHandlerInterface
    {
        return new class ($response) implements RequestHandlerInterface {
            public function __construct(private readonly ?ResponseInterface $response) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response ?? new Response()->withHeader('X-Handled', '1');
            }
        };
    }

    #[Test]
    public function disabledPassesThroughWithoutCorsHeaders(): void
    {
        $middleware = new CorsMiddleware(new Container(), enabled: false);

        $response = $middleware->process(new ServerRequest(), $this->handler());

        self::assertSame('1', $response->getHeaderLine('X-Handled'));
        self::assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function enabledAddsCorsHeadersToResponse(): void
    {
        $middleware = new CorsMiddleware(new Container(), allowOrigins: 'https://foo.com');

        $request = new ServerRequest(headers: ['Origin' => 'https://foo.com']);

        $response = $middleware->process($request, $this->handler());

        self::assertSame('https://foo.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function enabledCallableIsResolvedByContainer(): void
    {
        $middleware = new CorsMiddleware(
            new Container(),
            options: ['enabled' => static fn(ServerRequestInterface $request) => $request->getMethod() === 'GET']
        );

        $response = $middleware->process(new ServerRequest(method: 'GET'), $this->handler());

        self::assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function enabledCallableFalseSkipsCors(): void
    {
        $middleware = new CorsMiddleware(
            new Container(),
            options: ['enabled' => static fn(ServerRequestInterface $request) => false]
        );

        $response = $middleware->process(new ServerRequest(method: 'GET'), $this->handler());

        self::assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function optionsRequestReturnsEmptyResponseWithCorsHeaders(): void
    {
        $middleware = new CorsMiddleware(new Container());

        $response = $middleware->process(
            new ServerRequest(method: 'OPTIONS'),
            $this->handler()
        );

        self::assertSame('', (string) $response->getBody());
        self::assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function exceptionFromHandlerIsRethrownAfterSendingInstantly(): void
    {
        $middleware = new CorsMiddleware(new Container());

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \RuntimeException('boom');
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $middleware->process(new ServerRequest(), $handler);
    }

    #[Test]
    public function sendInstantlyOptionsReturnsEmptyResponse(): void
    {
        $middleware = new CorsMiddleware(new Container(), sendInstantly: true);

        $response = $middleware->process(new ServerRequest(method: 'OPTIONS'), $this->handler());

        self::assertSame('', (string) $response->getBody());
    }

    #[Test]
    public function sendInstantlyNonOptionsFallsThroughToHandler(): void
    {
        $middleware = new CorsMiddleware(new Container(), sendInstantly: true);

        $response = $middleware->process(new ServerRequest(), $this->handler());

        self::assertSame('1', $response->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function singleOriginAllowValueIsAlwaysUsed(): void
    {
        $middleware = new CorsMiddleware(new Container(), allowOrigins: 'https://allowed.com');

        $cors = $middleware->getCorsHandler(new ServerRequest(headers: ['Origin' => 'https://other.com']));

        self::assertSame('https://allowed.com', $cors->getResponse()->getHeaderLine('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function originKeywordReflectsRequestOrigin(): void
    {
        $middleware = new CorsMiddleware(new Container(), allowOrigins: 'ORIGIN');

        $cors = $middleware->getCorsHandler(new ServerRequest(headers: ['Origin' => 'https://foo.com']));

        self::assertSame('https://foo.com', $cors->getResponse()->getHeaderLine('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function multipleAllowsMatchingOriginIsReflected(): void
    {
        $middleware = new CorsMiddleware(
            new Container(),
            allowOrigins: ['https://a.com', 'https://b.com']
        );

        $cors = $middleware->getCorsHandler(new ServerRequest(headers: ['Origin' => 'https://b.com']));

        self::assertSame('https://b.com', $cors->getResponse()->getHeaderLine('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function multipleAllowsNotMatchingOriginSetsNoHeader(): void
    {
        $middleware = new CorsMiddleware(
            new Container(),
            allowOrigins: ['https://a.com', 'https://b.com']
        );

        $cors = $middleware->getCorsHandler(new ServerRequest(headers: ['Origin' => 'https://c.com']));

        self::assertFalse($cors->getResponse()->hasHeader('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function configureClosureCanModifyCorsHandler(): void
    {
        $middleware = new CorsMiddleware(
            new Container(),
            configure: static fn(CorsHandler $cors) => $cors->allowMethods(['GET', 'POST'])
        );

        $cors = $middleware->getCorsHandler(new ServerRequest());

        self::assertSame('GET, POST', $cors->getResponse()->getHeaderLine('Access-Control-Allow-Methods'));
    }

    #[Test]
    public function loggerClosureReceivesLogMessages(): void
    {
        $messages = [];

        $middleware = new CorsMiddleware(
            new Container(),
            logger: static function (string $message) use (&$messages) {
                $messages[] = $message;
            }
        );

        $middleware->process(new ServerRequest(), $this->handler());

        self::assertNotEmpty($messages);
        self::assertStringContainsString('Response CORS', $messages[0]);
    }

    #[Test]
    public function loggerInstanceReceivesDebugMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('debug');

        $middleware = new CorsMiddleware(new Container(), logger: $logger);

        $middleware->process(new ServerRequest(), $this->handler());
    }

    #[Test]
    public function deprecatedOptionsArrayStillConfiguresMiddleware(): void
    {
        $middleware = new CorsMiddleware(
            new Container(),
            options: ['enabled' => false]
        );

        $response = $middleware->process(new ServerRequest(), $this->handler());

        self::assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }
}
