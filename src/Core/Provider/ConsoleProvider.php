<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Console\IO\IOInterface;
use Windwalker\Core\Application\WindwalkerApplicationInterface;
use Windwalker\Core\Console\CoreConsole;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Environment\Environment;
use Windwalker\Environment\Platform;

/**
 * The ConsoleProvider class.
 * 
 * @since  2.0
 */
class ConsoleProvider implements ServiceProviderInterface
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
		$app = $container->get('application');

		$container->share(get_class($app), $app)
			->bindShared(CoreConsole::class, get_class($app))
			->bindShared(WindwalkerApplicationInterface::class, CoreConsole::class);

		// Input
		$container->share(IOInterface::class, $app->io)
			->alias('io', IOInterface::class);

		// Environment
		$container->prepareSharedObject(Environment::class)
			->alias('environment', Environment::class);

		$container->prepareSharedObject(Platform::class)
			->alias('platform', Platform::class);
	}
}
