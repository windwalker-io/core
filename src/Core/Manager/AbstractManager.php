<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Core\Manager\Event\InstanceCreatedEvent;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

/**
 * The AbstractManager class.
 */
abstract class AbstractManager implements EventAwareInterface
{
    use InstanceCacheTrait;
    use EventAwareTrait;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var Container
     */
    protected Container $container;

    /**
     * AbstractManager constructor.
     *
     * @param  Config     $config
     * @param  Container  $container
     */
    public function __construct(Config $config, Container $container)
    {
        $this->config    = $config->proxy($this->getConfigPrefix());
        $this->container = $container;
    }

    public function getDefaultName(): ?string
    {
        return $this->config->getDeep('default');
    }

    /**
     * create
     *
     * @param  string|null  $name
     * @param  mixed        ...$args
     *
     * @return  object
     */
    public function create(?string $name = null, ...$args): object
    {
        $name ??= $this->getDefaultName();

        if ($name === null) {
            throw new \InvalidArgumentException('Empty definition name.');
        }

        $args = $this->prepareArguments($name, $args);

        $define = $this->config->getDeep($this->getFactoryPath($name));

        $define ??= $this->getDefaultFactory($name, ...$args);

        if (!$define) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s::%s() definition: "%s" not found, the factory key is: %s',
                    static::class,
                    __FUNCTION__,
                    $name,
                    $this->getConfigPrefix() . '.' . $this->getFactoryPath($name)
                )
            );
        }

        $instance = $this->container->newInstance($define, $args);

        $this->emit(InstanceCreatedEvent::class, [
            'instance' => $instance,
            'instanceName' => $name,
            'args' => $args
        ]);

        return $instance;
    }

    protected function prepareArguments(string $name, array $args): array
    {
        $args['instanceName'] = $name;

        return $args;
    }

    /**
     * getDefaultFactory
     *
     * @param  string  $name
     * @param  mixed   ...$args
     *
     * @return  mixed
     */
    protected function getDefaultFactory(string $name, ...$args): mixed
    {
        return null;
    }

    public function get(?string $name = null, ...$args): object
    {
        $name ??= $this->getDefaultName();

        return $this->cacheStorage['instance.' . $name] ??= $this->create($name, ...$args);
    }

    abstract public function getConfigPrefix(): string;

    protected function getFactoryPath(string $name): string
    {
        return 'factories.instances.' . $name;
    }

    public function config(string $name, ?string $delimiter = '.'): mixed
    {
        return $this->config->getDeep($name, $delimiter);
    }
}
