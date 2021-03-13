<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Psr\EventDispatcher\EventDispatcherInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\EventDispatcher;
use Windwalker\Event\EventEmitter;

/**
 * The EventProvider class.
 */
class EventProvider implements ServiceProviderInterface
{
    /**
     * @var ApplicationInterface
     */
    protected ApplicationInterface $app;

    /**
     * EventProvider constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(ApplicationInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Registers the service provider with a DI container.
     *
     * @param  Container  $container  The DI container.
     *
     * @return  void
     * @throws \Windwalker\DI\Exception\DefinitionException
     */
    public function register(Container $container): void
    {
        $container->share('main.dispatcher', fn() => $this->app->getEventDispatcher());
        $container->prepareObject(
            EventEmitter::class,
            static function (EventEmitter $dispatcher, Container $container) {
                /** @var EventEmitter $mainDispatcher */
                $mainDispatcher = $container->get('main.dispatcher');

                $dispatcher->addDealer($mainDispatcher);

                return $dispatcher;
            }
        )
            ->alias(EventDispatcher::class, EventEmitter::class)
            ->alias(EventDispatcherInterface::class, EventEmitter::class);
    }
}
