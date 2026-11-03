<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Controller;

use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Controller\Exception\ControllerDispatchException;
use Windwalker\Core\Events\Web\AfterControllerDispatchEvent;
use Windwalker\Core\Events\Web\BeforeControllerDispatchEvent;
use Windwalker\Core\Http\OutsideRedirectResponse;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\Router;
use Windwalker\Event\EventEmitter;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;
use Windwalker\Tests\Core\Controller\Stub\ImplementsControllerInterfaceStub;
use Windwalker\Tests\Core\Controller\Stub\PlainTaskControllerStub;
use Windwalker\Tests\Core\Controller\Stub\TaskMappingControllerStub;

/**
 * Light integration tests for ControllerDispatcher, exercised through a real
 * WebApplication/AppContext (same tier-2 recipe as
 * tests/Core/Application/AppContextTest.php).
 */
class ControllerDispatcherTest extends TestCase
{
    private function createContext(string $uri = 'http://example.com/foo', string $method = 'GET'): AppContext
    {
        return TestWebAppFactory::create()->createContext(TestWebAppFactory::request($uri, $method));
    }

    /**
     * Navigator (used by ControllerDispatcher::handleResponse()) needs a
     * Router and EventEmitter shared on the context's own container; see
     * the "Pitfalls" section of docs/testing-architecture.md.
     */
    private function createContextWithNavigator(
        string $uri = 'http://example.com/foo',
        string $method = 'GET',
    ): AppContext {
        $ctx = $this->createContext($uri, $method);
        $ctx->getContainer()->share(Router::class, new Router());
        $ctx->getContainer()->share(EventEmitter::class, new EventEmitter());

        return $ctx;
    }

    #[Test]
    public function dispatchWithClosureControllerCallsContainer(): void
    {
        $ctx = $this->createContext();
        $ctx->setMatchedRoute(new Route('r', '/foo'));
        $ctx->setUrlVars(['id' => '9']);

        $result = $ctx->dispatchController(
            static fn(AppContext $app, string $id) => 'closure:' . $id,
        );

        self::assertSame('closure:9', $result);
    }

    #[Test]
    public function dispatchWithClassStringMethodSyntax(): void
    {
        $ctx = $this->createContext();
        PlainTaskControllerStub::$lastCalled = null;

        // Resolves to the array-callable branch internally, which (see
        // dispatchWithPlainCallableArrayInvokesMethodButReturnsNull()) does
        // not propagate the method's return value.
        $result = $ctx->dispatchController(PlainTaskControllerStub::class . '::save');

        self::assertNull($result);
        self::assertSame('save-called', PlainTaskControllerStub::$lastCalled);
    }

    #[Test]
    public function dispatchWithClassStringOnlyUsesDefaultTaskForMethod(): void
    {
        PlainTaskControllerStub::$lastCalled = null;
        $this->createContext(method: 'GET')->dispatchController(PlainTaskControllerStub::class);
        self::assertSame('index-called', PlainTaskControllerStub::$lastCalled);

        PlainTaskControllerStub::$lastCalled = null;
        $this->createContext(method: 'POST')->dispatchController(PlainTaskControllerStub::class);
        self::assertSame('save-called', PlainTaskControllerStub::$lastCalled);
    }

    #[Test]
    public function dispatchWithClassStringNormalizesUnderscoredDefaultTask(): void
    {
        // AbstractRequest rejects non-standard raw HTTP method strings, so
        // the underscored task name is supplied via the `_method` override
        // (read by AppRequest::getOverrideMethod()) instead of the raw
        // request method.
        $ctx = TestWebAppFactory::create()->createContext(
            TestWebAppFactory::request(method: 'POST')->withParsedBody(['_method' => 'some_method']),
        );
        PlainTaskControllerStub::$lastCalled = null;

        $ctx->dispatchController(PlainTaskControllerStub::class);

        self::assertSame('some_method-called', PlainTaskControllerStub::$lastCalled);
    }

    #[Test]
    public function dispatchWithControllerInterfaceArrayCallsExecute(): void
    {
        $ctx = $this->createContext();
        $ctx->setMatchedRoute(new Route('r', '/foo'));
        $ctx->setUrlVars(['id' => '42']);

        $result = $ctx->dispatchController([ImplementsControllerInterfaceStub::class, 'view']);

        self::assertSame('view:42', $result);
    }

    #[Test]
    public function dispatchWithPlainCallableArrayInvokesMethodButReturnsNull(): void
    {
        $ctx = $this->createContext();

        // The plain-callable branch of prepareArrayCallable() does not
        // propagate the method's return value (see
        // ControllerDispatcher::prepareArrayCallable()), so dispatch()
        // resolves to null even though 'save' is actually invoked.
        $result = $ctx->dispatchController([PlainTaskControllerStub::class, 'save']);

        self::assertNull($result);
    }

    #[Test]
    public function dispatchWithNonPublicMethodHandlerThrowsControllerDispatchException(): void
    {
        $this->expectException(ControllerDispatchException::class);

        $this->createContext()->dispatchController([PlainTaskControllerStub::class, 'secret']);
    }

    #[Test]
    public function dispatchWithWrongSizedArrayHandlerThrowsLogicException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('Controller callable should be array with 2 elements');

        $this->createContext()->dispatchController([PlainTaskControllerStub::class, 'save', 'extra']);
    }

    #[Test]
    public function dispatchWithNullControllerThrowsLogicException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('Controller not found');

        $this->createContext()->dispatchController(null);
    }

    #[Test]
    public function taskMappingAttributeOverridesTaskByName(): void
    {
        $ctx = $this->createContext(method: 'GET');
        TaskMappingControllerStub::$lastCalled = null;

        $ctx->dispatchController(TaskMappingControllerStub::class . '::index');

        self::assertSame('home-called', TaskMappingControllerStub::$lastCalled);
    }

    #[Test]
    public function taskMappingAttributeMethodOverrideWinsOverTaskName(): void
    {
        $ctx = $this->createContext(method: 'PUT');
        TaskMappingControllerStub::$lastCalled = null;

        $ctx->dispatchController(TaskMappingControllerStub::class . '::index');

        self::assertSame('update-called', TaskMappingControllerStub::$lastCalled);
    }

    #[Test]
    public function beforeControllerDispatchEventCanReplaceController(): void
    {
        $ctx = $this->createContext();
        $ctx->on(
            BeforeControllerDispatchEvent::class,
            static function (BeforeControllerDispatchEvent $event): void {
                $event->controller = static fn() => 'replaced';
            }
        );

        self::assertSame('replaced', $ctx->dispatchController(static fn() => 'original'));
    }

    #[Test]
    public function afterControllerDispatchEventCanReplaceResponse(): void
    {
        $ctx = $this->createContext();
        $ctx->on(
            AfterControllerDispatchEvent::class,
            static function (AfterControllerDispatchEvent $event): void {
                $event->response = 'replaced-response';
            }
        );

        self::assertSame('replaced-response', $ctx->dispatchController(static fn() => 'original'));
    }

    #[Test]
    public function handleResponseRewritesExternalRedirectLocationToSystemRoot(): void
    {
        $ctx = $this->createContextWithNavigator();

        $response = $ctx->dispatchController(static fn() => new RedirectResponse('http://evil.test/phish'));

        self::assertSame('http://example.com/', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function handleResponseKeepsLocalRedirectLocationUnchanged(): void
    {
        $ctx = $this->createContextWithNavigator();

        $response = $ctx->dispatchController(static fn() => new RedirectResponse('/bar'));

        self::assertSame('/bar', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function handleResponseLeavesOutsideRedirectResponseUntouched(): void
    {
        $ctx = $this->createContextWithNavigator();

        $response = $ctx->dispatchController(
            static fn() => OutsideRedirectResponse::from(new RedirectResponse('http://evil.test/phish'))
        );

        self::assertSame('http://evil.test/phish', $response->getHeaderLine('Location'));
    }
}
