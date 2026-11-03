<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Middleware\RoutingExcludesTrait;
use Windwalker\Core\Router\Route;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;

class RoutingExcludesTraitTest extends TestCase
{
    private function createSubject(mixed $excludes, string $uri = 'http://example.com/foo', ?string $routeName = null): object
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request(uri: $uri));

        if ($routeName !== null) {
            $ctx->setMatchedRoute(new Route($routeName));
        }

        return new class ($excludes, $ctx) {
            use RoutingExcludesTrait;

            public function __construct(private readonly mixed $excludes, AppContext $app)
            {
                $this->app = $app;
            }

            public function getExcludes(): mixed
            {
                return $this->excludes;
            }

            public function callIsExclude(): mixed
            {
                return $this->isExclude();
            }
        };
    }

    #[Test]
    public function isExcludeReturnsFalseWhenNoExcludes(): void
    {
        $subject = $this->createSubject(null);

        self::assertFalse($subject->callIsExclude());
    }

    #[Test]
    public function isExcludeHandlesClosureReturningBool(): void
    {
        $subject = $this->createSubject(
            static fn(?string $route, string $uri) => $uri === '/foo/',
        );

        self::assertTrue($subject->callIsExclude());
    }

    #[Test]
    public function isExcludeHandlesClosureReturningRedirectResponse(): void
    {
        $redirect = new RedirectResponse('/login');

        $subject = $this->createSubject(static fn() => $redirect);

        self::assertSame($redirect, $subject->callIsExclude());
    }

    #[Test]
    public function isExcludeHandlesClosureReturningString(): void
    {
        $subject = $this->createSubject(static fn() => '/login');

        $result = $subject->callIsExclude();

        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertSame('/login', $result->getHeaderLine('Location'));
    }

    #[Test]
    public function isExcludeMatchesUriExactly(): void
    {
        $subject = $this->createSubject(['/foo/'], 'http://example.com/foo');

        self::assertTrue($subject->callIsExclude());
    }

    #[Test]
    public function isExcludeMatchesUriWithWildcard(): void
    {
        $subject = $this->createSubject(['/foo/*'], 'http://example.com/foo/bar');

        self::assertTrue($subject->callIsExclude());
    }

    #[Test]
    public function isExcludeReturnsFalseWhenUriDoesNotMatch(): void
    {
        $subject = $this->createSubject(['/bar/'], 'http://example.com/foo');

        self::assertFalse($subject->callIsExclude());
    }

    #[Test]
    public function isExcludeMatchesRouteNameExactly(): void
    {
        $subject = $this->createSubject(['admin.dashboard'], routeName: 'admin.dashboard');

        self::assertTrue($subject->callIsExclude());
    }

    #[Test]
    public function isExcludeMatchesRouteNameWithWildcard(): void
    {
        $subject = $this->createSubject(['admin.*'], routeName: 'admin.dashboard');

        self::assertTrue($subject->callIsExclude());
    }

    #[Test]
    public function isExcludeReturnsFalseWhenRouteNameDoesNotMatch(): void
    {
        $subject = $this->createSubject(['admin.dashboard'], routeName: 'home');

        self::assertFalse($subject->callIsExclude());
    }

    #[Test]
    public function isExcludeFallsBackToInArrayCheckOnRoute(): void
    {
        // The matched route name literally equals one of the "/"-prefixed
        // (URI-style) exclude entries, which only the final in_array()
        // fallback can detect, since the loop only compares such entries
        // against the request URI, not the route name.
        $subject = $this->createSubject(
            ['/admin'],
            'http://example.com/other',
            '/admin',
        );

        self::assertTrue($subject->callIsExclude());
    }
}
