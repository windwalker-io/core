<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Language;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Language\LanguageNormalize;

/**
 * The Translator class.
 *
 * @method  static  string  translate($string)
 * @method  static  string  sprintf($string, ...$more)
 * @method  static  string  plural($string, $number)
 *
 * @since  2.0
 */
abstract class Translator extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'system.language';

	/**
	 * load
	 *
	 * @param string  $file
	 * @param string  $package
	 *
	 * @return  void
	 */
	public static function load($file, $package = null)
	{
		$container = static::getContainer();

		$config = $container->get('system.config');

		$format  = $config['language.format']  ? : 'ini';
		$default = $config['language.default'] ? : 'en-GB';
		$locale  = $config['language.locale']  ? : 'en-GB';

		$default = LanguageNormalize::toLanguageTag($default);
		$locale  = LanguageNormalize::toLanguageTag($locale);

		// If package name exists, we load package language first, that global can override it.
		if (is_string($package))
		{
			$package = $container->get('package.resolver')->getPackage($package);
		}

		if ($package instanceof AbstractPackage)
		{
			$path = $package->getDir() . '/Resources/language/%s/%s.%s';

			// Get Package language
			static::loadFile(sprintf($path, $default, $file, $format), $format);

			// If locale not equals default locale, load it to override default
			if ($locale != $default)
			{
				static::loadFile(sprintf($path, $locale, $file, $format), $format);
			}
		}

		// Get Global language
		$path = $config->get('path.languages') . '/%s/%s.%s';

		static::loadFile(sprintf($path, $default, $file, $format), $format);

		// If locale not equals default locale, load it to override default
		if ($locale != $default)
		{
			static::loadFile(sprintf($path, $locale, $file, $format), $format);
		}
	}

	/**
	 * loadFile
	 *
	 * @param string $file
	 * @param string $format
	 *
	 * @return  void
	 */
	protected static function loadFile($file, $format)
	{
		if (is_file($file))
		{
			static::getInstance()->load($file, $format);

			$container = static::getContainer();

			$config = $container->get('system.config');

			$loaded = $config['language.loaded'];

			$loaded[] = $file;

			$config->set('language.loaded', $loaded);
		}
	}

	/**
	 * Alias of translate().
	 *
	 * @param string $string
	 *
	 * @return  string
	 */
	public static function _($string)
	{
		return static::translate($string);
	}
}
