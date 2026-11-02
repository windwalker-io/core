<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\MicroApplication;
use Windwalker\Core\Router\Router;
use Windwalker\DI\Container;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;

class MicroApplicationTest extends TestCase
{
    #[Test]
    public function usesGivenContainerOrCreatesOne(): void
    {
        $container = new Container();

        self::assertSame($container, (new MicroApplication($container))->getContainer());
        self::assertInstanceOf(Container::class, (new MicroApplication())->getContainer());
    }

    #[Test]
    public function routingBootsAndRegistersRoutesToRouter(): void
    {
        $app = new MicroApplication();

        self::assertSame($app, $app->routing(fn($routes) => null));
        self::assertInstanceOf(Router::class, $app->service(Router::class));
    }

    #[Test]
    public function routedRequestIsDispatchedToController(): void
    {
        $app = new MicroApplication();
        $app->routing(
            static function (\Windwalker\Core\Router\RouteCreator $router) {
                $router->any('hello', '/hello')->handler(fn() => 'routed');
            }
        );

        $res = $app->runContext($app->createContext(TestWebAppFactory::request('http://example.com/hello')));

        self::assertSame('routed', (string) $res->getBody());
    }
}
