<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Provider;

use Psr\Log\LogLevel;
use Windwalker\Core\Logger\LoggerManager;
use Windwalker\Core\Logger\Monolog\MessageHandler;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The LoggerProvider class.
 * 
 * @since  2.1.1
 */
class LoggerProvider implements ServiceProviderInterface
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
		$closure = function(Container $container)
		{
			$manager = new LoggerManager($container->get('config')->get('path.logs'));

			$manager->addLogger(
				'message',
				$manager->createLogger(
					'message',
					LogLevel::DEBUG,
					$container->createSharedObject(MessageHandler::class)
				)
			);

			return $manager;
		};

		$container->share(LoggerManager::class, $closure);
	}
}
