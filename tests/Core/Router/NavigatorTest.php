<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Router;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\Router;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Event\EventEmitter;

class NavigatorTest extends TestCase
{
    private function createNavigator(Router $router, ?AppContext $app = null): Navigator
    {
        return new Navigator($router, new EventEmitter(), $app);
    }

    private function createAppStub(SystemUri $systemUri, ?Route $matchedRoute = null): AppContext
    {
        $app = $this->createStub(AppContext::class);
        $app->method('getSystemUri')->willReturn($systemUri);
        $app->method('getMatchedRoute')->willReturn($matchedRoute);

        return $app;
    }

    #[Test]
    public function isLocalUrl_withLeadingSlashIsAlwaysLocal(): void
    {
        $app = $this->createAppStub(new SystemUri('http://example.com/base'));
        $nav = $this->createNavigator(new Router(), $app);

        self::assertTrue($nav->isLocalUrl('/foo/bar'));
    }

    #[Test]
    public function isLocalUrl_matchesWhenUrlStartsWithRoot(): void
    {
        $app = $this->createAppStub(new SystemUri('http://example.com/base'));
        $nav = $this->createNavigator(new Router(), $app);

        self::assertTrue($nav->isLocalUrl('http://example.com/base/foo'));
        self::assertFalse($nav->isLocalUrl('http://other.com/foo'));
    }

    #[Test]
    public function validateRedirectUrl_returnsUrlWhenLocalOtherwiseRoot(): void
    {
        $systemUri = new SystemUri('http://example.com/base');
        $app = $this->createAppStub($systemUri);
        $nav = $this->createNavigator(new Router(), $app);

        self::assertSame('/foo', $nav->validateRedirectUrl('/foo'));
        self::assertSame($systemUri->root, $nav->validateRedirectUrl('http://other.com/foo'));
    }

    #[Test]
    public function absolute_withBaseOptionPrefixesUrl(): void
    {
        $app = $this->createAppStub(new SystemUri('http://example.com/base'));
        $nav = $this->createNavigator(new Router(), $app);

        self::assertSame('/my-base/foo', $nav->absolute('/foo', new \Windwalker\Core\Router\NavOptions(base: '/my-base')));
    }

    #[Test]
    public function absolute_withoutBaseDelegatesToSystemUri(): void
    {
        $app = $this->createAppStub(new SystemUri('http://example.com/base'));
        $nav = $this->createNavigator(new Router(), $app);

        self::assertSame('/foo', $nav->absolute('/foo'));
    }

    #[Test]
    public function findRoute_findsByNameDirectly(): void
    {
        $route = new Route('post.show', '/post/{id}');
        $router = new Router(['post.show' => $route]);
        $nav = $this->createNavigator($router);

        self::assertSame($route, $nav->findRoute('post.show'));
    }

    #[Test]
    public function findRoute_findsByViewClassWhenRouteContainsBackslash(): void
    {
        $route = new Route('post.show', '/post/{id}');
        $route->vars(['view' => 'App\\View\\PostView']);
        $router = new Router(['post.show' => $route]);
        $nav = $this->createNavigator($router);

        self::assertSame($route, $nav->findRoute('App\\View\\PostView'));
    }

    #[Test]
    public function findRoute_fallsBackToMatchedRouteNamespace(): void
    {
        $route = new Route('Post::show', '/post/{id}');
        $router = new Router(['Post::show' => $route]);

        $matched = new Route('Post::index', '/post');
        $matched->extra('namespace', 'Post');

        $app = $this->createAppStub(new SystemUri('http://example.com/'), $matched);
        $nav = $this->createNavigator($router, $app);

        self::assertSame($route, $nav->findRoute('show'));
    }

    #[Test]
    public function findRoute_returnsNullWhenNotFound(): void
    {
        $router = new Router();
        $app = $this->createAppStub(new SystemUri('http://example.com/'), null);
        $nav = $this->createNavigator($router, $app);

        self::assertNull($nav->findRoute('missing'));
    }

    #[Test]
    public function has_reflectsFindRouteResult(): void
    {
        $route = new Route('post.show', '/post/{id}');
        $router = new Router(['post.show' => $route]);
        $app = $this->createAppStub(new SystemUri('http://example.com/'), null);
        $nav = $this->createNavigator($router, $app);

        self::assertTrue($nav->has('post.show'));
        self::assertFalse($nav->has('missing'));
    }

    #[Test]
    public function inGroup_falseWhenNoMatchedRoute(): void
    {
        $app = $this->createAppStub(new SystemUri('http://example.com/'), null);
        $nav = $this->createNavigator(new Router(), $app);

        self::assertFalse($nav->inGroup('admin'));
    }

    #[Test]
    public function inGroup_trueWhenMatchedRouteIsInGroup(): void
    {
        $matched = new Route('show', '/show');
        $matched->extra('groups', ['admin' => []]);

        $app = $this->createAppStub(new SystemUri('http://example.com/'), $matched);
        $nav = $this->createNavigator(new Router(), $app);

        self::assertTrue($nav->inGroup('admin'));
        self::assertFalse($nav->inGroup('other'));
    }

    #[Test]
    public function isActive_falseWhenNoMatchedRoute(): void
    {
        $app = $this->createAppStub(new SystemUri('http://example.com/'), null);
        $nav = $this->createNavigator(new Router(), $app);

        self::assertFalse($nav->isActive('show'));
    }

    #[Test]
    public function isActive_matchesShortNameWithoutQuery(): void
    {
        $matched = new Route('Post::show', '/post/{id}');

        $app = $this->createAppStub(new SystemUri('http://example.com/'), $matched);
        $nav = $this->createNavigator(new Router(), $app);

        self::assertTrue($nav->isActive('show'));
        self::assertFalse($nav->isActive('other'));
    }

    #[Test]
    public function isActive_withArrayOfPathsMatchesAny(): void
    {
        $matched = new Route('Post::show', '/post/{id}');

        $app = $this->createAppStub(new SystemUri('http://example.com/'), $matched);
        $nav = $this->createNavigator(new Router(), $app);

        self::assertTrue($nav->isActive(['other', 'show']));
        self::assertFalse($nav->isActive(['other', 'another']));
    }
}
