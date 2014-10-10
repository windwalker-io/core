<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Package;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Registry\Registry;

/**
 * The PackageProvider class.
 * 
 * @since  {DEPLOY_VERSION}
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
