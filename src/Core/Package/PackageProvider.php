<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Package;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Registry\Registry;

/**
 * The PackageProvider class.
 * 
 * @since  2.0
 */
class PackageProvider implements ServiceProviderInterface
{
	/**
	 * Property config.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Property package.
	 *
	 * @var AbstractPackage
	 */
	protected $package;

	/**
	 * Class init.
	 *
	 * @param string          $name
	 * @param AbstractPackage $package
	 */
	public function __construct($name, AbstractPackage $package)
	{
		$this->name  = $name;
		$this->package = $package;
	}

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  void
	 */
	public function register(Container $container)
	{
	}
}
