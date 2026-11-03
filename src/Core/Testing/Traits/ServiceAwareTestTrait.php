<?php

declare(strict_types=1);

namespace Windwalker\Core\Testing\Traits;

use PHPUnit\Framework\Attributes\BeforeClass;
use ReflectionException;
use UnitEnum;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Testing\AbstractTestApp;
use Windwalker\DI\Container;
use Windwalker\DI\DIOptions;
use Windwalker\DI\Exception\DefinitionNotFoundException;
use Windwalker\DI\Exception\DefinitionResolveException;
use Windwalker\DI\Exception\DependencyResolutionException;
use Windwalker\Tests\TestApp;

/**
 * Creates a per-test-class child container from the shared app-backed test
 * context and exposes `make()`, `retrieve()`, and `call()` against it.
 *
 * Requires the consuming test suite to have a {@see AbstractTestApp}
 * subclass bootstrapped and `AbstractTestApp::$ctx` prepared before this
 * trait's `#[BeforeClass]` hook runs; it does not bootstrap the application
 * itself.
 */
trait ServiceAwareTestTrait
{
    protected static Container $container;

    protected static AppContext $appContext;

    /**
     * Create a new child container from every test class and store it in a static property.
     *
     * @return  void
     *
     * @throws  \Windwalker\DI\Exception\DefinitionException
     */
    #[BeforeClass(priority: 100)]
    public static function prepareAppContext(): void
    {
        $ctx = TestApp::prepareTestContext();

        static::$container = $ctx->container;
        static::$appContext = $ctx;
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     *
     * @return  T
     *
     * @throws  ReflectionException
     * @throws  DefinitionResolveException
     */
    public static function make(mixed $class, array $args = [], DIOptions $options = new DIOptions()): object
    {
        return static::$container->newInstance($class, $args, $options);
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $id
     *
     * @return  T
     * @throws  DefinitionNotFoundException
     * @throws  DependencyResolutionException
     */
    public static function retrieve(string $id, bool $forceNew = false, UnitEnum|string|null $tag = null): object
    {
        return static::$container->get($id, $forceNew, $tag);
    }

    public static function call(
        mixed $callable,
        array $args = [],
        ?object $context = null,
        DIOptions $options = new DIOptions(),
    ) {
        return static::$container->call(
            $callable,
            $args,
            $context,
            $options,
        );
    }
}
