<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Closure;
use Windwalker\Cache\CachePool;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\DI\Container;
use Windwalker\DI\Definition\ObjectBuilderDefinition;

/**
 * The CacheManager class.
 *
 * @method CachePool get(?string $name = null, ...$args)
 * @method CachePool create(?string $name = null, ...$args)
 */
#[Isolation]
class CacheManager extends AbstractManager
{
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

    public static function cachePoolFactory(
        string $storage,
        string|ObjectBuilderDefinition $serializer,
    ): Closure {
        return static function (Container $container, string $instanceName) use ($storage, $serializer): CachePool {
            return new CachePool(
                $container->resolve('cache.factories.storages.' . $storage, compact('instanceName')),
                $container->resolve($serializer),
                $container->get(LoggerManager::class)->get('error')
            );
        };
    }
}
