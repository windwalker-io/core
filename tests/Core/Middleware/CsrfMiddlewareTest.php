<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Middleware\CsrfMiddleware;
use Windwalker\Core\Security\Exception\InvalidTokenException;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Http\Response\Response;
use Windwalker\Session\Bridge\PhpBridge;
use Windwalker\Session\Handler\ArrayHandler;
use Windwalker\Session\Session;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;

class CsrfMiddlewareTest extends TestCase
{
    private function createContext(
        string $method = 'GET',
        array $headers = [],
    ): AppContext {
        $app = TestWebAppFactory::create();

        return $app->createContext(TestWebAppFactory::request(method: $method, headers: $headers));
    }

    private function shareSession(AppContext $ctx): void
    {
        $ctx->getContainer()->share(Session::class, new Session([], new PhpBridge(handler: new ArrayHandler())));
    }

    private function createMiddleware(AppContext $ctx, array $args = []): CsrfMiddleware
    {
        return $ctx->getContainer()->newInstance(CsrfMiddleware::class, $args);
    }

    private function next(): \Closure
    {
        return static fn(ServerRequestInterface $request) => new Response()->withHeader('X-Handled', '1');
    }

    #[Test]
    public function excludesClosureReturningTrueSkipsValidation(): void
    {
        $ctx = $this->createContext('POST');
        $middleware = $this->createMiddleware($ctx, [
            'excludes' => static fn() => true,
        ]);

        $result = $middleware->run($ctx->getServerRequest(), $this->next());

        self::assertSame('1', $result->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function excludesClosureReturningResponseIsReturnedDirectly(): void
    {
        $redirect = new RedirectResponse('http://example.com/elsewhere');

        $ctx = $this->createContext('POST');
        $middleware = $this->createMiddleware($ctx, [
            'excludes' => static fn() => $redirect,
        ]);

        $result = $middleware->run($ctx->getServerRequest(), $this->next());

        self::assertSame($redirect, $result);
    }

    #[Test]
    public function excludesClosureReturningStringIsWrappedInRedirectResponse(): void
    {
        $ctx = $this->createContext('POST');
        $middleware = $this->createMiddleware($ctx, [
            'excludes' => static fn() => 'http://example.com/elsewhere',
        ]);

        $result = $middleware->run($ctx->getServerRequest(), $this->next());

        self::assertInstanceOf(RedirectResponse::class, $result);
    }

    #[Test]
    public function excludesArrayMatchingUriSkipsValidation(): void
    {
        $ctx = $this->createContext('POST');
        $middleware = $this->createMiddleware($ctx, [
            'excludes' => ['/foo/'],
        ]);

        $result = $middleware->run($ctx->getServerRequest(), $this->next());

        self::assertSame('1', $result->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function excludesArrayNotMatchingUriContinuesValidation(): void
    {
        $ctx = $this->createContext('POST');
        $middleware = $this->createMiddleware($ctx, [
            'excludes' => ['/bar/'],
        ]);

        // No session bound, so CsrfService::checkToken() always passes.
        $result = $middleware->run($ctx->getServerRequest(), $this->next());

        self::assertSame('1', $result->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function nonWorkingMethodSkipsValidation(): void
    {
        $ctx = $this->createContext('GET');
        $middleware = $this->createMiddleware($ctx);

        $result = $middleware->run($ctx->getServerRequest(), $this->next());

        self::assertSame('1', $result->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function workingMethodWithoutSessionPassesValidation(): void
    {
        $ctx = $this->createContext('POST');
        $middleware = $this->createMiddleware($ctx);

        // No Session bound -> CsrfService::checkToken() returns true always.
        $result = $middleware->run($ctx->getServerRequest(), $this->next());

        self::assertSame('1', $result->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function customWorkingMethodsOptionIsRespected(): void
    {
        $ctx = $this->createContext('PATCH');
        $this->shareSession($ctx);
        $middleware = $this->createMiddleware($ctx, [
            'workingMethods' => ['post'],
        ]);

        // PATCH is not in the custom working methods list, so validation is skipped
        // even though session is bound (which would otherwise fail the token check).
        $result = $middleware->run($ctx->getServerRequest(), $this->next());

        self::assertSame('1', $result->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function invalidTokenWithoutOnInvalidRethrowsException(): void
    {
        $ctx = $this->createContext('POST');
        $this->shareSession($ctx);
        $middleware = $this->createMiddleware($ctx, [
            'invalidMessage' => 'Bad token here',
        ]);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Bad token here');

        $middleware->run($ctx->getServerRequest(), $this->next());
    }

    #[Test]
    public function invalidTokenWithOnInvalidCallbackContinuesToNext(): void
    {
        $ctx = $this->createContext('POST');
        $this->shareSession($ctx);

        $caught = null;

        $middleware = $this->createMiddleware($ctx, [
            'onInvalid' => static function (InvalidTokenException $e) use (&$caught) {
                $caught = $e;
            },
        ]);

        $result = $middleware->run($ctx->getServerRequest(), $this->next());

        self::assertInstanceOf(InvalidTokenException::class, $caught);
        self::assertSame('1', $result->getHeaderLine('X-Handled'));
    }

    #[Test]
    public function apiCallForcesGetInputMethod(): void
    {
        $ctx = $this->createContext('POST', ['Content-Type' => 'application/json']);
        $this->shareSession($ctx);

        self::assertTrue($ctx->isApiCall());

        $this->expectException(InvalidTokenException::class);

        $middleware = $this->createMiddleware($ctx, [
            'inputMethod' => 'POST',
        ]);

        // Even though inputMethod is configured as 'POST', isApiCall() forces 'GET',
        // and no csrf token is present on the query string, so validation fails.
        $middleware->run($ctx->getServerRequest(), $this->next());
    }

    #[Test]
    public function deprecatedOptionsArrayOverridesWorkingMethods(): void
    {
        $ctx = $this->createContext('PATCH');
        $this->shareSession($ctx);
        $middleware = $this->createMiddleware($ctx, [
            'options' => ['working_methods' => ['post']],
        ]);

        $result = $middleware->run($ctx->getServerRequest(), $this->next());

        self::assertSame('1', $result->getHeaderLine('X-Handled'));
    }
}
