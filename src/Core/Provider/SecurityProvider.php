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
use Windwalker\Crypt\Crypt;
use Windwalker\Crypt\CryptInterface;
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
		$container->bind(CipherInterface::class, function (Container $container)
		{
			$config = $container->get('config');
			$class = $this->getCipher($config->get('crypt.cipher', 'blowfish'));

			return $container->newInstance($class);
		});

		$container->share(Crypt::class, function (Container $container)
		{
			$config = $container->get('config');
			$key = md5('Windwalker-Crypt::' . $config->get('system.secret'));

			return $container->newInstance(Crypt::class, ['key' => $key]);
		})->bindShared(CryptInterface::class, Crypt::class);
	}

	/**
	 * getCipher
	 *
	 * @param   string  $cipher
	 *
	 * @return  string
	 */
	protected function getCipher($cipher)
	{
		switch ($cipher)
		{
			case 'blowfish':
			case 'bf':
				return BlowfishCipher::class;
			case 'des3':
			case '3des':
				return Des3Cipher::class;
			case 'aes-256':
			case 'aes':
				return Aes256Cipher::class;
		}

		return PhpAesCipher::class;
	}
}
