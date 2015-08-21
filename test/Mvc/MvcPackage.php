<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Test\Mvc;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Test\Mvc\Provider\TestMvcProvider;
use Windwalker\DI\Container;

/**
 * The StubPackage class.
 * 
 * @since  2.1.1
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
