<?php

declare(strict_types=1);

namespace Windwalker\Core\Factory;

use Psr\Log\LoggerInterface;
use Windwalker\Cache\CachePool;
use Windwalker\Cache\Storage\FileStorage;
use Windwalker\Cache\Storage\StorageInterface;
use Windwalker\Core\DI\ServiceFactoryInterface;
use Windwalker\Core\DI\ServiceFactoryTrait;
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
    ) {
        return #[Factory]
        static function (
            Container $container,
            string|\UnitEnum|null $tag = null,
        ) use (
            $storage,
            $serializer
        ): CachePool {
            return new CachePool(
                $container->resolve(StorageInterface::class, ['cacheTag' => $tag], tag: $storage),
                $container->resolve($serializer),
                $container->get(LoggerInterface::class, tag: 'error')
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

    public static function cachePoolFactory(
        string $storage,
        string|ObjectBuilderDefinition $serializer,
    ): \Closure {
        return #[Factory]
        static function (
            Container $container,
            string $instanceName
        ) use (
            $storage,
            $serializer
        ): CachePool {
            return new CachePool(
                $container->resolve('cache.factories.storages.' . $storage, compact('instanceName')),
                $container->resolve($serializer),
                $container->get(LoggerInterface::class, tag: 'error')
            );
        };
    }
}
