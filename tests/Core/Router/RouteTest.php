<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Router;

use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Router\NavConstantInterface;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\RouteUri;
use Windwalker\DI\Definition\ObjectBuilderDefinition;
use Windwalker\Uri\Uri;

class RouteTest extends TestCase
{
    #[Test]
    public function handler_withoutTasksSetsAllHandlers(): void
    {
        $route = new Route('show', '/show');

        $route->handler('MyController');

        self::assertSame(['*' => 'MyController'], $route->getHandlers());
    }

    #[Test]
    public function handler_withListTasksUsesFirstTaskOnly(): void
    {
        $route = new Route('show', '/show');

        $route->handler('MyController', 'index');

        self::assertSame(['*' => ['MyController', 'index']], $route->getHandlers());
    }

    #[Test]
    public function handler_withNamedTasksMapsEachMethod(): void
    {
        $route = new Route('show', '/show');

        $route->handler('MyController', get: 'index', post: 'store');

        self::assertSame(
            [
                'get' => ['MyController', 'index'],
                'post' => ['MyController', 'store'],
            ],
            $route->getHandlers()
        );
    }

    #[Test]
    public function controller_delegatesToHandler(): void
    {
        $route = new Route('show', '/show');

        $route->controller('MyController');

        self::assertSame(['*' => 'MyController'], $route->getHandlers());
    }

    #[Test]
    public function alias_mergesUniqueAliases(): void
    {
        $route = new Route('show', '/show');

        $route->alias('a')->alias(['b', 'a']);

        self::assertSame(['b', 'a'], array_values($route->getOption('aliases')));
    }

    #[Test]
    public function alias_withNullClearsAliases(): void
    {
        $route = (new Route('show', '/show'))->alias('a');

        $route->alias(null);

        self::assertSame([], $route->getOption('aliases'));
    }

    #[Test]
    public function isGroup_matchesGroupKeys(): void
    {
        $route = (new Route('show', '/show'))->groups(['admin' => [], 'api' => []]);

        self::assertTrue($route->isGroup('admin'));
        self::assertTrue($route->isGroup('other', 'api'));
        self::assertFalse($route->isGroup('other'));
    }

    #[Test]
    public function sanitize_normalizesPathSeparators(): void
    {
        self::assertSame('/foo/bar', Route::sanitize('foo//bar/'));
        self::assertSame('/', Route::sanitize(''));
    }

    #[Test]
    public function compile_appliesGroupPrefixNamespaceAndMethods(): void
    {
        $route = (new Route('show', '/show/{id}'))
            ->alias('legacy')
            ->groups(
                [
                    'post' => [
                        'prefix' => 'post',
                        'namespace' => 'Post',
                        'methods' => ['GET'],
                    ],
                ]
            );

        $compiled = $route->compile();

        self::assertSame('/post/show/{id}', $compiled->getPattern());
        self::assertSame('Post::show', $compiled->getName());
        self::assertSame(['GET'], $compiled->getMethods());
        self::assertSame('Post', $compiled->getNamespace());
        self::assertSame(['Post::legacy'], $compiled->getOption('aliases'));
        // compile() returns a clone and keeps the original untouched.
        self::assertSame('show', $route->getName());
    }

    #[Test]
    public function compile_throwsLogicExceptionWhenPatternMissing(): void
    {
        $route = new Route('show', '/show');
        $route->clear('pattern');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Route: show has no pattern.');

        $route->compile();
    }

    #[Test]
    public function redirect_withClosureCallsApp(): void
    {
        $route = new Route('show', '/show');
        $route->redirect(fn() => 'from-closure');

        $handler = $route->getHandlers()['*'];

        $nav = $this->createStub(Navigator::class);
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('call')
            ->willReturn('called-result');

        self::assertSame('called-result', $handler($nav, $app));
    }

    #[Test]
    public function redirect_withUriInterfaceReturnsSameInstance(): void
    {
        $uri = $this->createStub(UriInterface::class);

        $route = new Route('show', '/show');
        $route->redirect($uri);

        $handler = $route->getHandlers()['*'];

        $nav = $this->createStub(Navigator::class);
        $app = $this->createStub(ApplicationInterface::class);

        self::assertSame($uri, $handler($nav, $app));
    }

    #[Test]
    public function redirect_withAbsoluteUrlStringReturnsUriInstance(): void
    {
        $route = new Route('show', '/show');
        $route->redirect('https://example.com/foo');

        $handler = $route->getHandlers()['*'];

        $nav = $this->createStub(Navigator::class);
        $app = $this->createStub(ApplicationInterface::class);

        $result = $handler($nav, $app);

        self::assertInstanceOf(Uri::class, $result);
        self::assertSame('https://example.com/foo', (string) $result);
    }

    #[Test]
    public function redirect_withRelativeStringDelegatesToNavigator(): void
    {
        $route = new Route('show', '/show');
        $route->redirect('other.route', ['a' => 1], NavConstantInterface::TYPE_FULL);

        $handler = $route->getHandlers()['*'];

        $navResult = $this->createStub(RouteUri::class);

        $nav = $this->createMock(Navigator::class);
        $nav->expects($this->once())
            ->method('to')
            ->with('other.route', ['a' => 1], NavConstantInterface::TYPE_FULL)
            ->willReturn($navResult);

        $app = $this->createStub(ApplicationInterface::class);

        self::assertSame($navResult, $handler($nav, $app));
    }

    #[Test]
    public function jsonSerialize_convertsMiddlewareDefinitionsToClassNames(): void
    {
        $route = new Route('show', '/show');
        $route->middleware(new ObjectBuilderDefinition(\stdClass::class));
        $route->middleware(new class {
        });

        $data = $route->jsonSerialize();

        self::assertSame(\stdClass::class, $data['options']['middlewares'][0]);
        self::assertIsString($data['options']['middlewares'][1]);
    }
}
