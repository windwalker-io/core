<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Manager\CryptoManager;
use Windwalker\Core\Manager\HasherManager;
use Windwalker\Crypt\Hasher\HasherInterface;
use Windwalker\Crypt\Hasher\PasswordHasher;
use Windwalker\Crypt\Hasher\PasswordHasherInterface;
use Windwalker\Crypt\Symmetric\CipherInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

class SecurityProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->prepareSharedObject(CryptoManager::class);
        $container->prepareSharedObject(HasherManager::class);

        $container->bindShared(
            CipherInterface::class,
            function (Container $container, ?string $tag = null) {
                return $container->get(CryptoManager::class)->get($tag);
            }
        );

        $container->bindShared(
            HasherInterface::class,
            function (Container $container, ?string $tag = null) {
                return $container->get(HasherManager::class)->get($tag);
            }
        );

        $container->bindShared(
            PasswordHasher::class,
            function (Container $container) {
                return $container->get(HasherManager::class)->get('password');
            }
        )
            ->alias(PasswordHasherInterface::class, PasswordHasher::class);
    }
}
