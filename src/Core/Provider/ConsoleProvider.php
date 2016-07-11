<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Console\IO\IOInterface;
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
	 * Property app.
	 *
	 * @var CoreConsole
	 */
	protected $app;

	/**
	 * Class init.
	 *
	 * @param CoreConsole $app
	 */
	public function __construct(CoreConsole $app)
	{
		$this->app = $app;
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
		$container->share(CoreConsole::class, $this->app);

		// Input
		$container->share(IOInterface::class, $this->app->io)
			->alias('io', IOInterface::class);

		$closure = function(Container $container)
		{
			return new Environment;
		};

		// Environment
		$container->share(Environment::class, $closure)
			->alias('environment', Environment::class);

		$closure = function(Container $container)
		{
			return $container->get('environment')->platform;
		};

		$container->share(Platform::class, $closure)
			->alias('platform', Platform::class);
	}
}
 