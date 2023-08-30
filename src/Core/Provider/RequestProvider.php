<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\Context\RequestAppContextInterface;
use Windwalker\Core\Event\EventCollector;
use Windwalker\Core\Event\EventDispatcherRegistry;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\EventEmitter;
use Windwalker\Http\Request\ServerRequest;

/**
 * The RequestProvider class.
 *
 * @level 3
 */
class RequestProvider implements ServiceProviderInterface
{
    /**
     * @param  Container  $container
     *
     * @return  void
     *
     * @throws \Windwalker\DI\Exception\DefinitionException
     *
     * @level 3
     */
    public function register(Container $container): void
    {
        $container->alias(ApplicationInterface::class, RequestAppContextInterface::class);
        $container->alias(ServerRequestInterface::class, ServerRequest::class);

        $container->share(
            Config::class,
            $container->getParameters()
        );

        $container->share(
            EventCollector::class,
            fn(Container $container) => new EventCollector($container->getParam('app.debug'))
        );

        $container->bind(
            EventEmitter::class,
            function (Container $container) {
                return $container->get(EventDispatcherRegistry::class)->createDispatcher(
                    $container->get(EventCollector::class)
                );
            }
        );
    }
}
