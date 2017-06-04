<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

use Windwalker\Core\Queue\Driver\QueueDriverInterface;
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
		$container->prepareSharedObject(QueueFactory::class)
			->alias('queue.factory', QueueFactory::class);

		$container->share(QueueDriverInterface::class, function (Container $container)
		{
			$factory = $container->get('queue.factory');

			return $factory->getDriver();
		})->alias('queue.driver', QueueDriverInterface::class);

		$container->prepareSharedObject(QueueManager::class);

		$container->whenCreating(Worker::class)
			->setArgument('logger', function (Container $container)
			{
			    return $container->get('logger')->getLogger('queue');
			});

		$container->prepareSharedObject(Worker::class)->alias('queue.worker', Worker::class);
	}
}
