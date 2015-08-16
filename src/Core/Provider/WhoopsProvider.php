<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT Taiwan, Inc. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Provider;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * Class WhoopsProvider
 *
 * @since 1.0
 */
class WhoopsProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		$config = $container->get('system.config');

		if ($config->get('system.debug'))
		{
			$whoops = new \Whoops\Run;

			$handler = new \Whoops\Handler\PrettyPageHandler;

			$whoops->pushHandler($handler);

			$whoops->pushHandler(function($exception, $inspector, $run) use ($container)
			{
				if (!$container->exists('system.collector'))
				{
					return;
				}

				$collector = $container->get('system.collector');

				/** @var \Exception $exception */
				$collector['exception'] = array(
					'type'    => get_class($exception),
					'message' => $exception->getMessage(),
					'code'    => $exception->getCode(),
					'file'    => $exception->getFile(),
					'line'    => $exception->getLine(),
					'trace'   => $exception->getTrace()
				);
			});

			$whoops->register();

			$container->share('system.debugger', $whoops)
				->alias('whoops', 'system.debugger')
				->alias('debugger', 'system.debugger');

			$container->share('whoops.handler', $handler);
		}
	}
}
 