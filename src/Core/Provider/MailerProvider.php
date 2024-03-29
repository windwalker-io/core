<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Symfony\Component\Mailer\Transport;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\Core\Mailer\MailerInterface;
use Windwalker\Core\Manager\MailerManager;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The MailerProvider class.
 */
class MailerProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $container->prepareSharedObject(MailerManager::class);

        $container->bindShared(
            Mailer::class,
            fn(MailerManager $manager) => $manager->get()
        )
            ->alias(MailerInterface::class, Mailer::class);

        $container->bind(
            Transport::class,
            fn(Container $container) => new Transport(
                $container->call([Transport::class, 'getDefaultFactories'])
            )
        );
    }
}
