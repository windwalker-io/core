<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Router\Route;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;

/**
 * Light integration tests for AppContext (and AppContextTrait) created by a real WebApplication.
 */
class AppContextTest extends TestCase
{
    protected WebApplication $app;

    #[Before]
    protected function setUpApp(): void
    {
        $this->app = TestWebAppFactory::create();
    }

    private function createContext(string $uri = 'http://example.com/foo?a=1', string $method = 'GET'): AppContext
    {
        return $this->app->createContext(TestWebAppFactory::request($uri, $method));
    }

    #[Test]
    public function getRootAppReturnsCreatingApplication(): void
    {
        self::assertSame($this->app, $this->createContext()->getRootApp());
    }

    #[Test]
    public function eachContextHasItsOwnChildContainer(): void
    {
        $a = $this->createContext();
        $b = $this->createContext();

        self::assertNotSame($a, $b);
        self::assertNotSame($a->getContainer(), $b->getContainer());
        self::assertNotSame($this->app->getContainer(), $a->getContainer());
    }

    #[Test]
    public function requestMethodUsesOverrideForWebCallsAndRawForApiCalls(): void
    {
        $web = $this->app->createContext(
            TestWebAppFactory::request(method: 'POST')
                ->withParsedBody(['_method' => 'PUT'])
        );

        self::assertSame('POST', $web->getRequestRawMethod());
        self::assertSame('PUT', $web->getRequestMethod());

        $api = $this->app->createContext(
            TestWebAppFactory::request(method: 'POST', headers: ['Content-Type' => ['application/json']])
                ->withParsedBody(['_method' => 'PUT'])
        );

        self::assertTrue($api->isApiCall());
        self::assertSame('POST', $api->getRequestMethod());
    }

    #[Test]
    public function inputAndHeaderReadFromRequest(): void
    {
        $ctx = $this->app->createContext(
            TestWebAppFactory::request('http://example.com/?a=1', headers: ['X-Foo' => ['bar']])
        );

        self::assertSame('1', $ctx->input('a'));
        self::assertSame('bar', $ctx->getHeader('X-Foo'));
    }

    #[Test]
    public function setUrlVarsUpdatesAppRequest(): void
    {
        $ctx = $this->createContext();
        $ctx->setMatchedRoute(new Route('r', '/foo'));

        $ctx->setUrlVars(['id' => '9']);

        self::assertSame(['id' => '9'], $ctx->getUrlVars());
        self::assertSame('9', $ctx->input('id'));
    }

    #[Test]
    public function getStageReturnsNullWithoutRouteAndNamespaceWithRoute(): void
    {
        $ctx = $this->createContext();

        self::assertNull($ctx->getStage());

        $ctx->setMatchedRoute(new Route('r', '/foo')->extraValues(['namespace' => 'admin']));

        self::assertSame('admin', $ctx->getStage());
    }

    #[Test]
    public function getMessagesIsEmptyWithoutSession(): void
    {
        self::assertSame([], $this->createContext()->getMessages());
    }

    #[Test]
    public function dispatchControllerSetsControllerAndReturnsResult(): void
    {
        $ctx = $this->createContext();
        $controller = static fn(AppContext $app) => $app->input('a') . '!';

        self::assertSame('1!', $ctx->dispatchController($controller));
        self::assertSame($controller, $ctx->getController());
    }

    #[Test]
    public function dispatchWithInputRestoresRequestAndControllerAfterwards(): void
    {
        $ctx = $this->createContext();
        $ctx->setController($original = fn() => 'orig');
        $request = $ctx->getAppRequest();

        $result = $ctx->dispatchWithInput(
            fn(AppContext $app) => $app->input('a') . $app->input('b'),
            ['b' => 'B'],
        );

        self::assertSame('1B', $result);
        self::assertSame($original, $ctx->getController());
        self::assertSame($request, $ctx->getAppRequest());
    }
}
