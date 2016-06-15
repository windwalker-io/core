<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Application\AbstractWebApplication;
use Windwalker\Core\Application\WindwalkerWebApplication;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\IO\PsrInput;
use Windwalker\Registry\Registry;

/**
 * The WebProvider class.
 * 
 * @since  2.0
 */
class WebProvider implements ServiceProviderInterface
{
	public function boot(Container $container)
	{

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
		$app = $container->get('system.application');

		// Input
		$container->share('system.input', function (Container $container)
		{
		    return PsrInput::create($container->get('system.application')->getRequest());
		})->alias('input', 'system.input');

		// Environment
		$container->share('system.environment', $app->getEnvironment())
			->alias('environment', 'system.environment');

		$container->share('system.browser', $app->getEnvironment()->getBrowser());
		$container->share('system.platform', $app->getEnvironment()->getPlatform());

		// Uri
		$container->alias('uri', 'system.uri')
			->share(
				'system.uri',
				function ($container) use ($app)
				{
					return $container->get('system.application')->getServer()->getUriData();
				}
			);
	}
}
 