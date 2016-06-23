<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Language;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Language\LanguageNormalize;
use Windwalker\Language\Language;

/**
 * The Translator class.
 *
 * @see  \Windwalker\Language\Language
 *
 * @method  static  string    translate($string)
 * @method  static  string    sprintf($string, ...$more)
 * @method  static  string    plural($string, ...$number)
 * @method  static  string    getOrphans()
 * @method  static  string    setTraceLevelOffset($level)
 * @method  static  string    getTraceLevelOffset()
 * @method  static  Language  getInstance()
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
	protected static $_key = 'language';

	/**
	 * load
	 *
	 * @param string $file
	 * @param string $format
	 * @param string $package
	 *
	 * @return void
	 */
	public static function loadFile($file, $format = 'ini', $package = null)
	{
		$container = static::getContainer();

		$config = $container->get('config');

		$format = $format ? : $config->get('language.format', 'ini');

		$default = $config['language.default'] ? : 'en-GB';
		$locale  = $config['language.locale']  ? : 'en-GB';

		$ext = $format == 'yaml' ? 'yml' : $format;

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
			if (!$config->get('language.debug') || $locale == $default)
			{
				static::loadLanguageFile(sprintf($path, $default, $file, $ext), $format);
			}

			// If locale not equals default locale, load it to override default
			if ($locale != $default)
			{
				static::loadLanguageFile(sprintf($path, $locale, $file, $format), $format);
			}
		}

		// Get Global language
		$path = $config->get('path.languages') . '/%s/%s.%s';

		if (!$config->get('language.debug') || $locale == $default)
		{
			static::loadLanguageFile(sprintf($path, $default, $file, $format), $format);
		}

		// If locale not equals default locale, load it to override default
		if ($locale != $default)
		{
			static::loadLanguageFile(sprintf($path, $locale, $file, $format), $format);
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
	protected static function loadLanguageFile($file, $format)
	{
		if (is_file($file))
		{
			static::getInstance()->load($file, $format);

			$container = static::getContainer();

			$config = $container->get('config');

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
		$instance = static::getInstance();

		$instance->setTraceLevelOffset(1);

		$result = $instance->translate($string);

		$instance->setTraceLevelOffset(0);

		return $result;
	}

	/**
	 * __callStatic
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return  mixed
	 */
	public static function __callStatic($method, $args)
	{
		$instance = static::getInstance();

		$instance->setTraceLevelOffset(3);

		$result = parent::__callStatic($method, $args);

		$instance->setTraceLevelOffset(0);

		return $result;
	}
}
