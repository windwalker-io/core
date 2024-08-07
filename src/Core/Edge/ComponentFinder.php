<?php

declare(strict_types=1);

namespace Windwalker\Core\Edge;

use Windwalker\Attributes\AttributesAccessor;
use Windwalker\Cache\CachePool;
use Windwalker\Cache\Serializer\JsonSerializer;
use Windwalker\Cache\Serializer\PhpFileSerializer;
use Windwalker\Cache\Storage\ForeverFileStorage;
use Windwalker\Cache\Storage\PhpFileStorage;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Edge\Attribute\EdgeComponent;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Utilities\ClassFinder;
use Windwalker\DI\Attributes\Autowire;

class ComponentFinder
{
    protected CachePool $cache;

    public function __construct(
        protected ApplicationInterface $app,
        #[Autowire] protected ClassFinder $classFinder,
        protected PathResolver $pathResolver
    ) {
        //
    }

    public function find(): array
    {
        if ($this->app->isDebug()) {
            return $this->removeCacheAndScan();
        }

        $caches = $this->getCache()
            ->call(
                'caches.json',
                fn () => $this->scan()
            );

        return (array) $caches;
    }

    protected function removeCacheAndScan(): array
    {
        $this->getCache()->delete('caches.json');

        return $this->scan();
    }

    public function getCache(): CachePool
    {
        return $this->cache ??= new CachePool(
            new PhpFileStorage(
                $this->getCachePath(),
                [
                    'deny_access' => true,
                    'deny_code' => "<" . "?php defined('WINDWALKER_ROOT') or die; ?>",
                    'extension' => '.php',
                    'expiration_format' => null,
                ]
            ),
            new PhpFileSerializer()
        );
    }

    public function getCachePath(): string
    {
        return $this->pathResolver->resolve('@cache/renderer/components');
    }

    /**
     * @return  array|mixed
     */
    protected function getScanNames(): mixed
    {
        return $this->app->config('renderer.edge.component_scans') ?? [];
    }

    /**
     * @return  array
     */
    protected function scan(): array
    {
        $found = [];

        foreach ($this->getScanNames() as $scanName) {
            $classes = iterator_to_array($this->classFinder->findClasses($scanName, true));

            foreach ($classes as $class) {
                $attr = AttributesAccessor::getFirstAttributeInstance(
                    $class,
                    EdgeComponent::class,
                    \ReflectionAttribute::IS_INSTANCEOF
                );

                if ($attr) {
                    $found[$attr->name] = $class;
                }
            }
        }

        return $found;
    }
}
