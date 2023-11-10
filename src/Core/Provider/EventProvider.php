<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Psr\EventDispatcher\EventDispatcherInterface;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\EventDispatcher;
use Windwalker\Event\EventEmitter;

/**
 * The EventProvider class.
 *
 * @level 2
 */
class EventProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param  Container  $container  The DI container.
     *
     * @return  void
     * @throws DefinitionException
     */
    public function register(Container $container): void
    {
        $container->prepareObject(EventEmitter::class)
            ->alias(EventDispatcher::class, EventEmitter::class)
            ->alias(EventDispatcherInterface::class, EventEmitter::class);
    }
}
