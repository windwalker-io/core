<?php

declare(strict_types=1);

namespace Windwalker\Core\Factory;

use Psr\Log\LoggerInterface;
use Windwalker\Cache\CachePool;
use Windwalker\Cache\Storage\DatabaseStorage;
use Windwalker\Cache\Storage\FileStorage;
use Windwalker\Cache\Storage\StorageInterface;
use Windwalker\Core\DI\ServiceFactoryInterface;
use Windwalker\Core\DI\ServiceFactoryTrait;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Attributes\Factory;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\DI\Container;
use Windwalker\DI\Definition\ObjectBuilderDefinition;

#[Isolation]
class CacheFactory implements ServiceFactoryInterface
{
    use ServiceFactoryTrait;

    public function getClassName(): ?string
    {
        return CachePool::class;
    }

    public function getConfigPrefix(): string
    {
        return 'cache';
    }

    /**
     * getDefaultFactory
     *
     * @param  string  $name
     * @param  mixed   ...$args
     *
     * @return  mixed
     *
     * @since  4.0
     */
    protected function getDefaultFactory(string $name, ...$args): mixed
    {
        return $this->config->getDeep($this->getFactoryPath($this->getDefaultName()));
    }

    public static function createCachePool(
        string $storage,
        string|ObjectBuilderDefinition $serializer,
        string|\UnitEnum|null|false $tagStorage = false,
        ?string $group = null,
        ?int $defaultTtl = null,
        string $className = CachePool::class,
    ) {
        return #[Factory]
        static function (
            Container $container,
            string|\UnitEnum|null $tag = null,
        ) use (
            $storage,
            $serializer,
            $tagStorage,
            $className,
            $defaultTtl,
        ): CachePool {
            $tag = $group ?? $tag;

            if ($tagStorage !== false && $tagStorage !== null) {
                $tagStorage = $container->get(StorageInterface::class, tag: $tagStorage);
            }

            return new $className(
                $container->resolve(StorageInterface::class, ['cacheTag' => $tag], tag: $storage),
                $container->resolve($serializer),
                $container->get(LoggerInterface::class, tag: 'cache'),
                defaultTtl: $defaultTtl,
                tagPool: $tagStorage
            );
        };
    }

    public static function fileStorage(): \Closure
    {
        return #[Factory]
        static fn(
            Container $container,
            string $cacheTag,
            ?string $tag
        ): FileStorage => new FileStorage(
            $container->getParam('@cache') . '/' . $cacheTag,
            []
        );
    }

    public static function dbStorage(
        ?string $connection = null,
        string $table = 'cache_items',
        array $columns = [],
    ): \Closure {
        return #[Factory]
        static fn(
            Container $container,
            string $cacheTag,
            ?string $tag
        ): DatabaseStorage => new DatabaseStorage(
            db: $container->get(DatabaseAdapter::class, tag: $connection),
            group: $cacheTag,
            table: $table,
            columns: $columns,
        );
    }

    public static function cachePoolFactory(
        string $storage,
        string|ObjectBuilderDefinition $serializer,
        string|\UnitEnum|null|false $tagStorage = false,
        ?string $group = null,
        ?int $defaultTtl = null,
        string $className = CachePool::class,
    ): \Closure {
        return #[Factory]
        static function (
            Container $container,
            string $instanceName
        ) use (
            $storage,
            $serializer,
            $group,
            $tagStorage,
            $className,
            $defaultTtl,
        ): CachePool {
            $cacheTag = $group ?? $instanceName;

            if ($tagStorage !== false && $tagStorage !== null) {
                $tagStorage = $container->get(StorageInterface::class, tag: $tagStorage);
            }

            return new $className(
                $container->resolve('cache.factories.storages.' . $storage, compact('instanceName', 'cacheTag')),
                $container->resolve($serializer),
                $container->get(LoggerInterface::class, tag: 'error'),
                defaultTtl: $defaultTtl,
                tagPool: $tagStorage,
            );
        };
    }
}
