<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Application\AbstractWebApplication;
use Windwalker\Console\Console;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The SystemProvider class.
 * 
 * @since  2.0
 */
class SystemProvider implements ServiceProviderInterface
{
	/**
	 * Property app.
	 *
	 * @var AbstractWebApplication|Console
	 */
	protected $app;

	/**
	 * Class init.
	 *
	 * @param AbstractWebApplication|Console $app
	 */
	public function __construct($app)
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
		$container->share('system.application', $this->app)
			// ->alias('application', 'system.application')
			->alias('app', 'system.application');

		$container->share('system.config', $this->app->config)
			->alias('config', 'system.config');
	}
}
 