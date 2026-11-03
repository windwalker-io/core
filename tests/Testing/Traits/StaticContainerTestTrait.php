<?php

declare(strict_types=1);

namespace Windwalker\Tests\Testing\Traits;

use PHPUnit\Framework\Attributes\BeforeClass;
use ReflectionException;
use Windwalker\Tests\TestApp;
use UnitEnum;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Container;
use Windwalker\DI\DIOptions;
use Windwalker\DI\Exception\DefinitionNotFoundException;
use Windwalker\DI\Exception\DefinitionResolveException;
use Windwalker\DI\Exception\DependencyResolutionException;
use Windwalker\ORM\ORM;

trait StaticContainerTestTrait
{
    protected static Container $container;

    /**
     * Create a new child container from every test class and store it in a static property.
     *
     * @return  void
     *
     * @throws  \Windwalker\DI\Exception\DefinitionException
     */
    #[BeforeClass(priority: 100)]
    public static function prepareChildContainer(): void
    {
        $parentContainer = TestApp::$ctx->container;

        static::$container = $parentContainer->createChild();

        // Keep the ORM and DatabaseAdapter always one instance in the child container.
        static::$container->share(
            ORM::class,
            fn() => $parentContainer->get(ORM::class)
        );

        static::$container->share(
            DatabaseAdapter::class,
            fn() => $parentContainer->get(DatabaseAdapter::class)
        );
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
        DIOptions $options = new DIOptions()
    ) {
        return static::$container->call(
            $callable,
            $args,
            $context,
            $options
        );
    }
}
