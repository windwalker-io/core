<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Router;

use FastRoute\BadRouteException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\RouteCreator;
use Windwalker\Core\Router\Router;
use Windwalker\Http\Request\ServerRequest;

class RouterTest extends TestCase
{
    #[Test]
    public function getRoute_findsRouteByName(): void
    {
        $home = new Route('home', '/');
        $router = new Router(['home' => $home]);

        self::assertSame($home, $router->getRoute('home'));
    }

    #[Test]
    public function getRoute_findsRouteByAlias(): void
    {
        $route = new Route('post.show', '/post/{id}')->alias('post.view');
        $router = new Router(['post.show' => $route]);

        self::assertSame($route, $router->getRoute('post.view'));
    }

    #[Test]
    public function getRoute_returnsNullWhenNotFound(): void
    {
        $router = new Router();

        self::assertNull($router->getRoute('missing'));
    }

    #[Test]
    public function register_mergesCompiledRoutesFromCreator(): void
    {
        $router = new Router();

        $router->register(
            function (RouteCreator $creator) {
                $creator->get('home', '/');
            }
        );

        $route = $router->getRoute('home');

        self::assertInstanceOf(Route::class, $route);
        self::assertSame('/', $route->getPattern());
        self::assertSame(['GET'], $route->getMethods());
    }

    #[Test]
    public function filterRoute_returnsTrueWhenNoRestrictions(): void
    {
        $router = new Router();
        $route = new Route('home', '/');

        self::assertTrue(
            $router->filterRoute(new ServerRequest(method: 'POST'), $route)
        );
    }

    #[Test]
    public function filterRoute_returnsFalseWhenMethodMismatch(): void
    {
        $router = new Router();
        $route = new Route('home', '/')->methods('POST');

        self::assertFalse(
            $router->filterRoute(new ServerRequest(method: 'GET'), $route)
        );
    }

    #[Test]
    public function filterRoute_returnsTrueWhenMethodMatches(): void
    {
        $router = new Router();
        $route = new Route('home', '/')->methods('GET');

        self::assertTrue(
            $router->filterRoute(new ServerRequest(method: 'get'), $route)
        );
    }

    #[Test]
    public function filterRoute_returnsFalseWhenHostMismatch(): void
    {
        $router = new Router();
        $route = new Route('home', '/')->hosts('example.com');

        self::assertFalse(
            $router->filterRoute(new ServerRequest(uri: 'http://other.com/'), $route)
        );
    }

    #[Test]
    public function filterRoute_returnsTrueWhenHostMatchesWithVariable(): void
    {
        $router = new Router();
        $route = new Route('home', '/')->hosts('{sub:\w+}.example.com');

        self::assertTrue(
            $router->filterRoute(new ServerRequest(uri: 'http://foo.example.com/'), $route)
        );
        self::assertSame(['sub' => 'foo'], $route->getVars());
    }

    #[Test]
    public function filterRoute_returnsFalseWhenSchemeMismatch(): void
    {
        $router = new Router();
        $route = new Route('home', '/')->scheme('https');

        self::assertFalse(
            $router->filterRoute(new ServerRequest(uri: 'http://example.com/'), $route)
        );
    }

    #[Test]
    public function filterRoute_returnsTrueWhenSchemeMatches(): void
    {
        $router = new Router();
        $route = new Route('home', '/')->scheme('https');

        self::assertTrue(
            $router->filterRoute(new ServerRequest(uri: 'https://example.com/'), $route)
        );
    }

    #[Test]
    public function matchPattern_returnsTrueAndCapturesVariables(): void
    {
        $router = new Router();

        $matched = $router->matchPattern('{sub:\w+}.example.com', 'foo.example.com', $variables);

        self::assertTrue($matched);
        self::assertSame(['sub' => 'foo'], $variables);
    }

    #[Test]
    public function matchPattern_returnsFalseWhenNotMatched(): void
    {
        $router = new Router();

        $matched = $router->matchPattern('{sub:\w+}.example.com', 'example.org', $variables);

        self::assertFalse($matched);
        self::assertSame([], $variables);
    }

    #[Test]
    public function match_returnsRouteWithMergedVarsWhenFound(): void
    {
        $home = new Route('home', '/');
        $postShow = new Route('post.show', '/post/{id:\d+}');

        $router = new Router(['home' => $home, 'post.show' => $postShow]);

        $matched = $router->match(new ServerRequest(uri: '/post/42', method: 'GET'));

        self::assertSame('post.show', $matched->getName());
        self::assertSame(['id' => '42'], $matched->getVars());
        // Original route is untouched (match() clones before mutating vars).
        self::assertSame([], $postShow->getVars());
    }

    #[Test]
    public function match_usesExplicitRouteOverRequestPath(): void
    {
        $home = new Route('home', '/');

        $router = new Router(['home' => $home]);

        $matched = $router->match(new ServerRequest(uri: '/ignored', method: 'GET'), '/');

        self::assertSame('home', $matched->getName());
    }

    #[Test]
    public function match_throwsRouteNotFoundExceptionWhenNoRouteMatches(): void
    {
        $router = new Router(['home' => new Route('home', '/')]);

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessageMatches('/Unable to find this route/');

        $router->match(new ServerRequest(uri: '/missing', method: 'GET'));
    }

    #[Test]
    public function patternToRegex_buildsNamedCapturingRegex(): void
    {
        $router = new Router();

        $items = $router->patternToRegex('/post/{id:\d+}');

        self::assertSame('/post/(?P<id>\d+)', $items[0][0]);
        self::assertSame(['id' => 'id'], $items[0][1]);
    }

    #[Test]
    public function patternToRegex_throwsOnDuplicatePlaceholder(): void
    {
        $router = new Router();

        $this->expectException(BadRouteException::class);
        $this->expectExceptionMessageMatches('/Cannot use the same placeholder "id" twice/');

        $router->patternToRegex('/post/{id}/{id}');
    }

    #[Test]
    public function patternToRegex_throwsWhenRegexContainsCapturingGroup(): void
    {
        $router = new Router();

        $this->expectException(BadRouteException::class);
        $this->expectExceptionMessageMatches('/contains a capturing group/');

        $router->patternToRegex('/post/{id:(\d+)}');
    }
}
