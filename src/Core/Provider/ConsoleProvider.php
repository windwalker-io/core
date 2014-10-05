<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Console\WindwalkerConsole;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Environment\Environment;

/**
 * The ConsoleProvider class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class ConsoleProvider implements ServiceProviderInterface
{
	/**
	 * Property app.
	 *
	 * @var WindwalkerConsole
	 */
	protected $app;

	/**
	 * Class init.
	 *
	 * @param WindwalkerConsole $app
	 */
	public function __construct(WindwalkerConsole $app)
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
		// Input
		$container->share('system.io', $this->app->io)
			->alias('io', 'system.io');

		$closure = function(Container $container)
		{
			return new Environment;
		};

		// Environment
		$container->share('system.environment', $closure)
			->alias('environment', 'system.environment');

		$closure = function(Container $container)
		{
			return $container->get('system.environment')->server;
		};

		$container->share('system.server', $closure);
	}
}
 