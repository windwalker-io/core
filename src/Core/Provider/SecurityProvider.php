<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Security\CsrfGuard;
use Windwalker\Crypt\Cipher\Aes256Cipher;
use Windwalker\Crypt\Cipher\BlowfishCipher;
use Windwalker\Crypt\Cipher\CipherInterface;
use Windwalker\Crypt\Cipher\Des3Cipher;
use Windwalker\Crypt\Cipher\PhpAesCipher;
use Windwalker\Crypt\Cipher\SodiumCipher;
use Windwalker\Crypt\Crypt;
use Windwalker\Crypt\CryptInterface;
use Windwalker\Crypt\HasherInterface;
use Windwalker\Crypt\Password;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The SecurityProvider class.
 *
 * @since  2.1.1
 */
class SecurityProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container $container The DI container.
     *
     * @return  void
     */
    public function register(Container $container)
    {
        $container->prepareSharedObject(CsrfGuard::class)
            ->alias('security.csrf', CsrfGuard::class);

        // Always new instance in every usage
        $container->bind(CipherInterface::class, function (Container $container) {
            $config = $container->get('config');
            $class  = $this->getCipher($config->get('crypt.cipher', 'blowfish'));

            return $container->newInstance($class);
        });

        $container->share(Crypt::class, function (Container $container) {
            $config = $container->get('config');
            $key    = md5('Windwalker-Crypt::' . $config->get('system.secret'));

            return $container->newInstance(Crypt::class, ['key' => $key]);
        })->bindShared(CryptInterface::class, Crypt::class);

        $container->share(Password::class, function (Container $container) {
            $config = $container->get('config');

            return new Password(
                $this->getHashingAlgorithm($config->get('crypt.hash_algo', 'blowfish')),
                (int) $config->get('crypt.hash_cost')
            );
        })->bindShared(HasherInterface::class, Password::class);
    }

    /**
     * getCipher
     *
     * @param   string $cipher
     *
     * @return  string
     */
    protected function getCipher($cipher)
    {
        switch ($cipher) {
            case 'blowfish':
            case 'bf':
                return BlowfishCipher::class;

            case 'des3':
            case '3des':
                return Des3Cipher::class;

            case 'aes-256':
            case 'aes':
                return Aes256Cipher::class;

            case 'sodium':
                return SodiumCipher::class;
        }

        return PhpAesCipher::class;
    }

    /**
     * getPasswordAlgorithm
     *
     * @param string $type
     *
     * @return  int
     */
    public function getHashingAlgorithm($type)
    {
        switch ($type) {
            case 'md5':
                return Password::MD5;

            case 'sha256':
                return Password::SHA256;

            case 'sha512':
                return Password::SHA512;

            case 'argon2':
                return Password::SODIUM_ARGON2;

            case 'scrypt':
                return Password::SODIUM_SCRYPT;

            case 'blowfish':
            case 'bf':
            default:
                return Password::BLOWFISH;
        }
    }
}
