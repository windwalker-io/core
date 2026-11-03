<?php

declare(strict_types=1);

namespace Windwalker\Core\Testing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Http\Event\RequestEvent;
use Windwalker\Http\Factory\ServerRequestFactory;
use Windwalker\Http\Request\ServerRequest;

/**
 * Base class for a host application's tier-3/4 app-backed test app.
 *
 * A consuming app boots a real `WebApplication` once for its whole test
 * process and keeps a shared request-level {@see AppContext} around, so
 * tests do not have to reboot the application to retrieve services,
 * dispatch controllers, or hit a real database. This class provides that
 * lifecycle; a host app only needs to extend it with a concrete class (see
 * `tests/TestApp.php` in this package for the reference example) and wire
 * it up from its own PHPUnit bootstrap the way
 * {@see  \Windwalker\Core\Testing\Traits\DatabaseTestTrait},
 * {@see \Windwalker\Core\Testing\Traits\ServiceAwareTestTrait}, and
 * {@see \Windwalker\Core\Testing\Traits\ControllerTestTrait} expect.
 *
 * Do not redeclare `$instance`, `$ctx`, or `$sharedCtx` in a subclass: the
 * traits above reference them through `AbstractTestApp::...`, relying on PHP
 * sharing static-property storage between a parent and a child class that
 * does not redeclare the property.
 */
abstract class AbstractTestApp extends WebApplication
{
    public static self $instance;

    /**
     * Context a test class assigned to itself, usually in its own
     * `#[BeforeClass]` hook, when it needs to customize the request layer
     * (providers, bindings) instead of using {@see $sharedCtx}. Shared by
     * every test method in that class, the same way the static container in
     * `DITestTrait` is.
     */
    public static AppContext $ctx;

    /**
     * Context created once at bootstrap by {@see prepareSharedContext()},
     * for tests that do not need to build their own and just want a ready
     * request-level context to retrieve services from.
     */
    public static AppContext $sharedCtx;

    public function executeServerEvent(RequestEvent $event): ResponseInterface
    {
        $appContext = $this->createContextFromServerEvent($event);

        $event->endHandler = fn() => $this->stopContext($appContext);

        return $this->runContext($appContext);
    }

    public static function createTestContext(
        ?ServerRequestInterface $request = null,
        iterable $providers = [],
    ): AppContext {
        $app = static::$instance ?? throw new \RuntimeException('TestApp instance not set.');

        $request ??= ServerRequestFactory::createFromGlobals();

        $app->boot();

        // Level 2
        $container = $app->getContainer();

        // Level 3
        $container = $container->createChild();

        if ($request !== null) {
            $container->share(ServerRequest::class, $request);
        }

        foreach ($providers as $provider) {
            $container->registerServiceProvider($provider);
        }

        $app->registerListeners($container);

        $app->bootProvidersBeforeRequest($container);

        $appContext = $container->get(AppContext::class);

        $appContext->disableDebugProfiler(true);

        // Before Run Context
        $container = $appContext->getContainer();
        $request = $container->get(ServerRequestInterface::class);

        // @event
        $event = $app->emit(
            new BeforeRequestEvent(container: $container, request: $request),
        );

        // @event
        $appContext->emit(
            new BeforeRequestEvent(container: $container, request: $event->request),
        );

        return $appContext;
    }

    /**
     * Create and prepare the context shared by every test that does not
     * build its own, storing it in {@see $sharedCtx}. Call this once from
     * the host app's PHPUnit bootstrap.
     */
    public function prepareSharedContext(
        ?ServerRequestInterface $request = null,
        iterable $providers = [],
    ): AppContext {
        $appContext = $this->createContext($request, $providers);

        static::$sharedCtx = $appContext;
        static::$ctx = $appContext;

        return $appContext;
    }

    public static function prepareTestContext(
        ?ServerRequestInterface $request = null,
        iterable $providers = [],
    ): AppContext {
        $appContext = static::$instance->createContext($request, $providers);

        static::$ctx = $appContext;

        return $appContext;
    }
}
