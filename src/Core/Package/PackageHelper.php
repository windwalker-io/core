<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Package;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Http\Response\Response;
use Windwalker\IO\Input;
use Windwalker\Ioc;
use Windwalker\Registry\Registry;

/**
 * The PackageHelper class.
 *
 * @see  Windwalker\Core\Package\PackageResolver
 *
 * @method  static  PackageResolver         getInstance()
 * @method  static  PackageResolver         registerPackages(array $packages)
 * @method  static  AbstractPackage         addPackage($alias, $package)
 * @method  static  AbstractPackage         getPackage($name = null)
 * @method  static  AbstractPackage         getCurrentPackage()
 * @method  static  string|AbstractPackage  getAlias($package)
 * @method  static  AbstractPackage         resolvePackage($name)
 * @method  static  PackageResolver         removePackage($name)
 * @method  static  AbstractPackage[]       getPackages()
 * @method  static  boolean                 exists($package)
 * @method  static  Registry                getConfig($package)
 *
 * @since  2.0
 */
class PackageHelper extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'package.resolver';

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
		return static::getDir($package);
	}

	/**
	 * getDir
	 *
	 * @param string $package
	 *
	 * @see  PackageResolver::getPath
	 *
	 * @return  string
	 */
	public static function getDir($package)
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
}
