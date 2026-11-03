<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Router;

use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Router\RouteCreator;

class RouteCreatorTest extends TestCase
{
    #[Test]
    public function any_withPatternCreatesRouteAndPrependsSlash(): void
    {
        $creator = new RouteCreator();

        $route = $creator->any('show', 'show/{id}');

        self::assertSame('/show/{id}', $route->getPattern());
        self::assertSame('show', $route->getName());
        self::assertSame(['show' => $route], $creator->getRoutes());
    }

    #[Test]
    public function any_withCallableGeneratesNameAndSetsHandler(): void
    {
        $creator = new RouteCreator();
        $handler = fn() => 'result';

        $route = $creator->any('/closure-route', $handler);

        self::assertSame('/closure-route', $route->getPattern());
        self::assertNotSame('/closure-route', $route->getName());
        self::assertSame(['*' => $handler], $route->getHandlers());
    }

    #[Test]
    public function any_inheritsMiddlewaresAndSubscribersFromGroup(): void
    {
        $creator = new RouteCreator();
        $route = null;

        $creator->group(
            'admin',
            [
                'middlewares' => ['AdminMiddleware'],
                'subscribers' => ['AdminSubscriber'],
            ],
            function (RouteCreator $router) use (&$route) {
                $route = $router->any('show', '/show');
            }
        );

        self::assertCount(1, $route->getMiddlewares());
        self::assertSame(['AdminSubscriber'], $route->getSubscribers());
        self::assertTrue($route->isGroup('admin'));
    }

    #[Test]
    public function group_returnsNewInstanceWithGroupOptionsApplied(): void
    {
        $creator = new RouteCreator();

        $admin = $creator->group('admin', ['prefix' => 'admin']);

        self::assertNotSame($creator, $admin);
        self::assertSame('admin', $admin->getOption('prefix'));
        self::assertSame([], $creator->getGroups());
    }

    #[Test]
    public function group_withCallbackRegistersNestedRoutesUnderGroup(): void
    {
        $creator = new RouteCreator();
        $nestedRoute = null;

        $creator->group(
            'admin',
            ['prefix' => 'admin'],
            function (RouteCreator $router) use (&$nestedRoute) {
                $nestedRoute = $router->any('show', '/show');
            }
        );

        self::assertTrue($nestedRoute->isGroup('admin'));
        // Group frame is popped off after the callback runs.
        self::assertSame([], $creator->getGroups());
    }

    #[Test]
    public function parents_mergesPreparedGroupOptionsIntoCurrent(): void
    {
        $creator = new RouteCreator();
        $creator->group('base', ['prefix' => 'base']);

        $creator->parents(['base']);

        self::assertSame('base', $creator->getOption('prefix'));
    }

    #[Test]
    public function parents_withEmptyArrayIsNoop(): void
    {
        $creator = new RouteCreator();

        self::assertSame($creator, $creator->parents([]));
    }

    #[Test]
    public function parents_withUnknownParentThrows(): void
    {
        $creator = new RouteCreator();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unable to find parent group: missing for route group: root');

        $creator->parents(['missing']);
    }

    #[Test]
    public function register_wrapsCallableInGroupFrame(): void
    {
        $creator = new RouteCreator();
        $groupsDuringCall = null;

        $creator->register(
            function (RouteCreator $router) use (&$groupsDuringCall) {
                $groupsDuringCall = $router->getGroups();
                $router->any('show', '/show');
            }
        );

        self::assertSame(['root' => []], $groupsDuringCall);
        // Group frame is popped off after the callable runs.
        self::assertSame([], $creator->getGroups());
        self::assertArrayHasKey('root.show', $creator->getRoutes());
    }

    #[Test]
    public function load_withCallableInvokesItDirectly(): void
    {
        $creator = new RouteCreator();

        $creator->load(
            function (RouteCreator $router) {
                $router->any('callable-route', '/callable-route');
            }
        );

        self::assertArrayHasKey('callable-route', $creator->getRoutes());
    }

    #[Test]
    public function load_withFilePathRequiresFileWithRouterInScope(): void
    {
        $creator = new RouteCreator();

        $creator->load(__DIR__ . '/fixtures/route-a.php');

        self::assertArrayHasKey('route-a', $creator->getRoutes());
    }

    #[Test]
    public function loadFolder_loadsAllPhpFilesInFolder(): void
    {
        $creator = new RouteCreator();

        $creator->loadFolder(__DIR__ . '/fixtures');

        $routes = $creator->getRoutes();

        self::assertArrayHasKey('route-a', $routes);
        self::assertArrayHasKey('route-b', $routes);
    }

    #[Test]
    public function compileRoutes_compilesAllRoutesKeyedByCompiledName(): void
    {
        $creator = new RouteCreator();

        $creator->group(
            'admin',
            ['prefix' => 'admin'],
            function (RouteCreator $router) {
                $router->any('show', '/show');
            }
        );

        $compiled = $creator->compileRoutes();

        self::assertArrayHasKey('show', $compiled);
        self::assertSame('/admin/show', $compiled['show']->getPattern());
    }
}
