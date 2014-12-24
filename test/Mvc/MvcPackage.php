<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\Mvc;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Test\Mvc\Provider\TestMvcProvider;
use Windwalker\DI\Container;

/**
 * The StubPackage class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class MvcPackage extends AbstractPackage
{
	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'mvc';

	/**
	 * registerProviders
	 *
	 * @param Container $container
	 *
	 * @return  void
	 */
	public function registerProviders(Container $container)
	{
		$container->registerServiceProvider(new TestMvcProvider);
	}
}
