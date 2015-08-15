<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Package;

use Windwalker\Console\Console;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Facade\AbstractFacade;
use Windwalker\DI\Container;
use Windwalker\Core\Ioc;
use Windwalker\Registry\Registry;

/**
 * The PackageHelper class.
 *
 * @method  static  PackageResolver  getInstance()
 * 
 * @since  2.0
 */
class PackageHelper extends AbstractFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'package.resolver';

	/**
	 * getPackage
	 *
	 * @param string $name
	 *
	 * @return  AbstractPackage
	 */
	public static function getPackage($name)
	{
		return static::getInstance()->getPackage($name);
	}

	/**
	 * getPackages
	 *
	 * @see  PackageResolver::getPackages
	 *
	 * @return  AbstractPackage[]
	 */
	public static function getPackages()
	{
		return static::getInstance()->getPackages();
	}

	/**
	 * getPath
	 *
	 * @param string $package
	 *
	 * @see  PackageResolver::getPath
	 *
	 * @return  string
	 */
	public static function getPath($package)
	{
		return static::getPackage($package)->getDir();
	}

	/**
	 * getClassName
	 *
	 * @param string $package
	 *
	 * @see  PackageResolver::getClassName
	 *
	 * @return  string
	 */
	public static function getClassName($package)
	{
		return get_class(static::getPackage($package));
	}

	/**
	 * getConfig
	 *
	 * @param string $package
	 *
	 * @return  Registry
	 */
	public static function getConfig($package)
	{
		return static::getInstance()->getConfig($package);
	}

	/**
	 * has
	 *
	 * @param string $package
	 *
	 * @see  PackageResolver::exists
	 *
	 * @return  boolean
	 */
	public static function exists($package)
	{
		return static::getInstance()->exists($package);
	}
}
