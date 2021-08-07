<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\DI\RequestBootableProviderInterface;
use Windwalker\Core\Manager\Logger;
use Windwalker\Core\Manager\LoggerManager;
use Windwalker\Core\Service\LoggerService;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The LoggerProvider class.
 */
class LoggerProvider implements ServiceProviderInterface, RequestBootableProviderInterface
{
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
        $container->prepareSharedObject(LoggerManager::class);
        $container->prepareSharedObject(LoggerService::class);
    }

    public function bootBeforeRequest(Container $container): void
    {
        Logger::$service = $container->get(LoggerService::class);
    }
}
