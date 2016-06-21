<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Language\Language;
use Windwalker\Language\Loader\FileLoader;

/**
 * The LanguageProvider class.
 * 
 * @since  2.0
 */
class LanguageProvider implements ServiceProviderInterface
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
		$closure = function(Container $container)
		{
			/** @var \Windwalker\Registry\Registry $config */
			$config = $container->get('config');

			$debug     = $config['system.debug'] ? : false;
			$langDebug = $config['language.debug'] ? : false;

			$language = new Language(
				$config->get('language.locale', 'en-GB'),
				$config->get('language.default', 'en-GB')
			);

			return $language->setDebug(($debug && $langDebug));
		};

		$container->share(Language::class, $closure)->alias('language', Language::class);
	}
}
