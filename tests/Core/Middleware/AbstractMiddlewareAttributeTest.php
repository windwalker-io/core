<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Middleware\AbstractMiddlewareAttribute;
use Windwalker\Core\Middleware\AttributeMiddlewareInterface;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\AttributesResolver;
use Windwalker\DI\Container;

class AbstractMiddlewareAttributeTest extends TestCase
{
    #[Test]
    public function invokeRunsCreatedMiddlewareAroundTheHandler(): void
    {
        $container = new Container();
        $request = $this->createStub(ServerRequestInterface::class);
        $container->share(ServerRequestInterface::class, $request);

        $middleware = $this->createStub(AttributeMiddlewareInterface::class);
        $middleware->method('run')->willReturnCallback(
            static fn(ServerRequestInterface $req, \Closure $next): mixed => 'middleware:' . $next(),
        );

        $attribute = new class ($middleware) extends AbstractMiddlewareAttribute {
            public function __construct(private readonly AttributeMiddlewareInterface $middleware) {}

            public function createMiddleware(Container $container): AttributeMiddlewareInterface
            {
                return $this->middleware;
            }
        };

        $handler = new AttributeHandler(
            static fn(): string => 'inner-result',
            new \ReflectionFunction(static fn() => null),
            null,
            new AttributesResolver($container),
            [],
            $container,
        );

        $wrapped = $attribute($handler);

        self::assertSame('middleware:inner-result', $wrapped());
    }
}
