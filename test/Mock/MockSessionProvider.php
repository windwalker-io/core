<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\Mock;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Session\Handler\NativeHandler;
use Windwalker\Session\Session;
use Windwalker\Session\Test\Mock\MockArrayBridge;

/**
 * The MockSessionProvider class.
 *
 * @since  {DEPLOY_VERSION}
 */
class MockSessionProvider implements ServiceProviderInterface
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
		$self = $this;

		$closure = function(Container $container) use ($self)
		{
			$sesion = new Session(new NativeHandler, null, null, new MockArrayBridge, array());

			return $sesion;
		};

		$container->share('system.session', $closure)
			->alias('session', 'system.session');
	}
}
