<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Provider;

use Windwalker\Application\AbstractWebApplication;
use Windwalker\Core\Application\Console;
use Windwalker\Core\Application\WebApplication;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Registry\Registry;

/**
 * The SystemProvider class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class SystemProvider implements ServiceProviderInterface
{
	/**
	 * Property app.
	 *
	 * @var AbstractWebApplication|WebApplication|Console
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
			->alias('app', 'system.application');

		$container->share('system.config', $this->app->config)
			->alias('config', 'system.config');
	}
}
 