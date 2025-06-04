<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Psr\Log\LoggerInterface;
use Windwalker\Core\DI\RequestBootableProviderInterface;
use Windwalker\Core\Manager\Logger;
use Windwalker\Core\Manager\LoggerManager;
use Windwalker\Core\Service\LoggerService;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
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
     * @throws DefinitionException
     */
    public function register(Container $container): void
    {
        $container->prepareSharedObject(LoggerManager::class);
        $container->prepareSharedObject(LoggerService::class);
        $container->bindShared(
            LoggerInterface::class,
            fn(LoggerManager $loggerManager, ?string $tag = null) => $loggerManager->get($tag)
        );
    }

    public function bootBeforeRequest(Container $container): void
    {
        Logger::setInstance($container->get(LoggerService::class));
    }
}
