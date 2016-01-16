<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Registry\Registry;
use Windwalker\SystemPackage\SystemPackage;
use Windwalker\Core\Provider;

/**
 * The main Windwalker instantiate class.
 *
 * This class will load in both Web and Console. Write some configuration if you want to use in all environment.
 *
 * @since  2.0
 */
abstract class Windwalker
{
	/**
	 * Load packages.
	 *
	 * If you want some packages run in both Web and Console, register them here.
	 *
	 * @return  AbstractPackage[]
	 */
	public static function loadPackages()
	{
		return array(
			'system' => new SystemPackage
		);
	}

	/**
	 * Load providers.
	 *
	 * If you want seom 3rd libraries tun in both Web and Console, register them here.
	 *
	 * @return  ServiceProviderInterface[]
	 */
	public static function loadProviders()
	{
		return array();
	}

	/**
	 * Load configuration files.
	 *
	 * @param   Registry  $config  The config registry object.
	 *
	 * @throws  \RuntimeException
	 *
	 * @return  void
	 */
	public static function loadConfiguration(Registry $config)
	{
		$config->loadFile($config['path.etc'] . '/config.yml', 'yaml');
	}

	/**
	 * Load routing profiles as an array.
	 *
	 * @return  array
	 */
	public static function loadRouting()
	{
		return array();
	}

	/**
	 * Prepare system path.
	 *
	 * Write your custom path to $config['path.xxx'].
	 *
	 * @param   Registry  $config  The config registry object.
	 *
	 * @return  void
	 */
	public static function prepareSystemPath(Registry $config)
	{
		$config['path.root']       = null;
		$config['path.bin']        = null;
		$config['path.cache']      = null;
		$config['path.etc']        = null;
		$config['path.logs']       = null;
		$config['path.resources']  = null;
		$config['path.source']     = null;
		$config['path.temp']       = null;
		$config['path.templates']  = null;
		$config['path.vendor']     = null;
		$config['path.public']     = null;
		$config['path.migrations'] = null;
		$config['path.seeders']    = null;
		$config['path.languages']  = null;
	}
}
