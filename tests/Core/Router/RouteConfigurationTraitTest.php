<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Router;

use BadMethodCallException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Router\Route;

/**
 * Tests RouteConfigurationTrait's logic through the concrete Route class.
 */
class RouteConfigurationTraitTest extends TestCase
{
    #[Test]
    public function methods_setsAndCollapsesMethodList(): void
    {
        $route = new Route('show', '/show');

        $route->methods(['get', 'post'], 'put');

        self::assertSame(['get', 'post', 'put'], $route->getMethods());
    }

    #[Test]
    public function methodHandler_mapsSingleMethodToHandler(): void
    {
        $route = new Route('show', '/show');

        $route->methodHandler('get', 'MyController', 'index');

        self::assertSame(['get' => ['MyController', 'index']], $route->getHandlers());
    }

    #[Test]
    public function handlers_withListArgsUsesMethodHandler(): void
    {
        $route = new Route('show', '/show');

        $route->handlers('get', 'MyController');

        self::assertSame(['get' => 'MyController'], $route->getHandlers());
    }

    #[Test]
    public function handlers_withMappingArgsDispatchesPerMethod(): void
    {
        $route = new Route('show', '/show');

        $route->handlers(get: 'GetController', fetch: 'FetchController', save: 'SaveController');

        // 'fetch' is processed after 'get' and re-sets the 'get' handler via fetchHandler().
        self::assertSame(
            [
                'get' => 'FetchController',
                'query' => 'FetchController',
                'post' => 'SaveController',
                'put' => 'SaveController',
                'patch' => 'SaveController',
            ],
            $route->getHandlers()
        );
    }

    #[Test]
    public function handlers_withNumericMappingKeyThrows(): void
    {
        $route = new Route('show', '/show');

        $this->expectException(InvalidArgumentException::class);

        $route->handlers(...[5 => 'MyController', 'post' => 'AnotherController']);
    }

    #[Test]
    public function allHandlers_setsWildcardMethod(): void
    {
        $route = new Route('show', '/show');

        $route->allHandlers('MyController', 'index');

        self::assertSame(['*' => ['MyController', 'index']], $route->getHandlers());
    }

    #[Test]
    public function saveHandler_setsPostPutPatch(): void
    {
        $route = new Route('show', '/show');

        $route->saveHandler('MyController');

        self::assertSame(
            [
                'post' => 'MyController',
                'put' => 'MyController',
                'patch' => 'MyController',
            ],
            $route->getHandlers()
        );
    }

    #[Test]
    public function fetchHandler_setsGetAndQuery(): void
    {
        $route = new Route('show', '/show');

        $route->fetchHandler('MyController');

        self::assertSame(
            [
                'get' => 'MyController',
                'query' => 'MyController',
            ],
            $route->getHandlers()
        );
    }

    #[Test]
    public function vars_mergesRecursively(): void
    {
        $route = new Route('show', '/show');

        $route->vars(['a' => 1])->vars(['b' => 2]);

        self::assertSame(['b' => 2, 'a' => 1], $route->getVars());
    }

    #[Test]
    public function layoutPaths_appendsPaths(): void
    {
        $route = new Route('show', '/show');

        $route->layoutPaths('a')->layoutPaths('b', 'c');

        self::assertSame(['a', 'b', 'c'], $route->getOption('layoutPaths'));
    }

    #[Test]
    public function middlewares_addsEachMiddleware(): void
    {
        $route = new Route('show', '/show');

        $route->middlewares(['Mid1', 'Mid2']);

        self::assertCount(2, $route->getMiddlewares());
    }

    #[Test]
    public function middleware_withFalsyValueIsIgnored(): void
    {
        $route = new Route('show', '/show');

        $route->middleware(null);

        self::assertSame([], $route->getMiddlewares());
    }

    #[Test]
    public function subscribe_withOneArgAppendsToList(): void
    {
        $route = new Route('show', '/show');

        $route->subscribe('a-subscriber');

        self::assertSame(['a-subscriber'], $route->getSubscribers());
    }

    #[Test]
    public function subscribe_withTwoArgsGroupsByEvent(): void
    {
        $route = new Route('show', '/show');

        $route->subscribe('SomeEvent', 'a-listener');

        self::assertSame(['SomeEvent' => ['a-listener']], $route->getSubscribers());
    }

    #[Test]
    public function subscribe_withThreeArgsSetsKeyedListener(): void
    {
        $route = new Route('show', '/show');

        $route->subscribe('Aware', 'SomeEvent', 'a-listener');

        self::assertSame(['Aware' => ['SomeEvent' => 'a-listener']], $route->getSubscribers());
    }

    #[Test]
    public function subscribe_withTooManyArgsThrows(): void
    {
        $route = new Route('show', '/show');

        $this->expectException(BadMethodCallException::class);

        $route->subscribe('a', 'b', 'c', 'd');
    }

    #[Test]
    public function subscribes_mergesRecursively(): void
    {
        $route = new Route('show', '/show');

        $route->subscribe('existing');
        $route->subscribes(['extra']);

        self::assertSame(['existing', 'extra'], $route->getSubscribers());
    }

    #[Test]
    public function hosts_mergesAndCollapsesHostList(): void
    {
        $route = new Route('show', '/show');

        $route->hosts('a.com')->hosts(['b.com', 'c.com']);

        self::assertSame(['a.com', 'b.com', 'c.com'], $route->getHosts());
    }

    #[Test]
    public function extra_setsNestedValue(): void
    {
        $route = new Route('show', '/show');

        $route->extra('foo.bar', 'baz');

        self::assertSame(['bar' => 'baz'], $route->getExtraValue('foo'));
        self::assertSame(['foo' => ['bar' => 'baz']], $route->getExtra());
    }

    #[Test]
    public function extraValues_mergesRecursively(): void
    {
        $route = new Route('show', '/show');

        $route->extraValues(['a' => 1])->extraValues(['b' => 2]);

        self::assertSame(['b' => 2, 'a' => 1], $route->getExtra());
    }

    #[Test]
    public function magicCall_delegatesToMethodHandlerForHandlerSuffix(): void
    {
        $route = new Route('show', '/show');

        $route->getHandler('MyController', 'index');

        self::assertSame(['get' => ['MyController', 'index']], $route->getHandlers());
    }

    #[Test]
    public function magicCall_withUnknownMethodThrows(): void
    {
        $route = new Route('show', '/show');

        $this->expectException(BadMethodCallException::class);

        $route->unknownMethod();
    }
}
