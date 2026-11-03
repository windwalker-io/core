<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\State\AppState;
use Windwalker\Core\View\BaseVM;
use Windwalker\Session\Session;
use Windwalker\Tests\Core\Application\Stub\SessionStub;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;
use Windwalker\Tests\Core\Application\Stub\ViewStub;
use Windwalker\Uri\Uri;

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

    #[Test]
    public function dispatchWithArrayDataReplacesQueryAndBody(): void
    {
        $ctx = $this->createContext('http://example.com/foo?a=1');

        $result = $ctx->dispatchWithInput(
            fn(AppContext $app) => $app->input('a'),
            ['a' => 'X'],
        );

        self::assertSame('X', $result);
        self::assertSame('1', $ctx->input('a'));
    }

    #[Test]
    public function dispatchWithServerRequestDataUsesGivenRequest(): void
    {
        $ctx = $this->createContext('http://example.com/foo?a=1');

        $result = $ctx->dispatchWithInput(
            fn(AppContext $app) => $app->input('c'),
            TestWebAppFactory::request('http://example.com/foo')->withParsedBody(['c' => 'C']),
        );

        self::assertSame('C', $result);
    }

    #[Test]
    public function redirectReturnsRedirectResponseWithLocation(): void
    {
        $response = $this->createContext()->redirect('/bar', 302);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/bar', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function renderViewRendersGivenViewObjectMergingOptions(): void
    {
        $ctx = $this->createContext();
        $view = new ViewStub();

        $response = $ctx->renderView($view, ['name' => 'Bob'], ['layout' => 'custom']);

        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(['default' => true, 'layout' => 'custom'], $body['options']);
        self::assertSame(['name' => 'Bob'], $body['data']);
        // Options are merged into the same instance since it was passed directly.
        self::assertSame(['default' => true, 'layout' => 'custom'], $view->options);
    }

    #[Test]
    public function renderViewResolvesStringToViewInstance(): void
    {
        $response = $this->createContext()->renderView(ViewStub::class, ['x' => '1']);

        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(['default' => true], $body['options']);
        self::assertSame(['x' => '1'], $body['data']);
    }

    #[Test]
    public function renderViewRendersRealViewModelThroughRealViewAndRenderer(): void
    {
        $app = TestWebAppFactory::createWithRendering();
        $ctx = $app->createContext(TestWebAppFactory::request());

        $vm = $ctx->make(BaseVM::class);

        $response = $ctx->renderView($vm, ['name' => 'Bob'], ['layout' => 'test-layout']);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Hello, Bob!', (string) $response->getBody());
    }

    #[Test]
    public function sethUriUpdatesAppRequestUri(): void
    {
        $ctx = $this->createContext('http://example.com/foo?a=1');

        $ctx->sethUri(new Uri('http://other.test/bar'));

        self::assertSame('http://other.test/bar', (string) $ctx->getUri());
    }

    #[Test]
    public function setSystemUriUpdatesAppRequestSystemUri(): void
    {
        $ctx = $this->createContext();
        $newSystemUri = new SystemUri('http://other.test/');

        $ctx->setSystemUri($newSystemUri);

        self::assertSame($newSystemUri, $ctx->getSystemUri());
    }

    #[Test]
    public function addMessageAddsFlashMessageThroughSession(): void
    {
        $ctx = $this->createContext();
        $session = new SessionStub();
        $ctx->getContainer()->share(Session::class, $session);

        $ctx->addMessage('hello', 'success');
        $ctx->addMessage(['a', 'b']);

        self::assertSame(
            ['success' => ['hello'], 'info' => ['a', 'b']],
            $session->getFlashBag()->peek()
        );
    }

    #[Test]
    public function getMessagesReadsFromSessionWhenRegistered(): void
    {
        $ctx = $this->createContext();
        $session = new SessionStub();
        $ctx->getContainer()->share(Session::class, $session);
        $session->addFlash('hi', 'info');
        $session->addFlash('bye', 'warning');

        self::assertSame(['hi'], $ctx->getMessages('info', peek: true));
        self::assertSame(
            ['info' => ['hi'], 'warning' => ['bye']],
            $ctx->getMessages(peek: true)
        );
        // get() (non-peek, with type) consumes the messages for that type.
        self::assertSame(['hi'], $ctx->getMessages('info'));
        self::assertNull($session->getFlashBag()->peek('info'));
    }

    #[Test]
    public function getMessagesWithoutTypeOrPeekConsumesAllFlashes(): void
    {
        $ctx = $this->createContext();
        $session = new SessionStub();
        $ctx->getContainer()->share(Session::class, $session);
        $session->addFlash('hi', 'info');

        self::assertSame(['info' => ['hi']], $ctx->getMessages());
        self::assertSame([], $session->getFlashBag()->peek());
    }

    #[Test]
    public function getStateAndStateReadFromSharedAppState(): void
    {
        $ctx = $this->createContext();
        $state = new AppState($ctx->getContainer());
        $ctx->setState($state);
        $state->set('foo', 'bar');

        self::assertSame($state, $ctx->getState());
        self::assertSame('bar', $ctx->getState()->get('foo', false));
        self::assertSame('bar', $ctx->state('foo', false));
    }

    #[Test]
    public function getStateWithPrefixReturnsPrefixedClone(): void
    {
        $ctx = $this->createContext();
        $state = new AppState($ctx->getContainer());
        $ctx->setState($state);
        $state->set('ns.foo', 'bar');

        $prefixed = $ctx->getState('ns');

        self::assertNotSame($state, $prefixed);
        self::assertSame('bar', $prefixed->get('foo', false));
    }
}
