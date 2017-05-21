<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The QueueProvider class.
 *
 * @since  __DEPLOY_VERSION__
 */
class QueueProvider implements ServiceProviderInterface
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
		$container->prepareSharedObject(QueueManager::class)
			->alias(QueueManager::class, 'queue');
	}
}
