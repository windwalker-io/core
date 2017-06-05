<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

use Windwalker\Core\Queue\Driver\QueueDriverInterface;
use Windwalker\Core\Queue\Failer\DatabaseQueueFailer;
use Windwalker\Core\Queue\Failer\QueueFailerInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The QueueProvider class.
 *
 * @since  3.2
 */
class QueueProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  void
	 * @throws \Windwalker\DI\Exception\DependencyResolutionException
	 */
	public function register(Container $container)
	{
		$container->prepareSharedObject(QueueManager::class);

		$container->share(Queue::class, function (Container $container)
		{
			return $container->get('queue.manager')->getManager();
		});

		// Worker
		$container->share(Worker::class, function (Container $container)
		{
		    return $container->newInstance(
		    	Worker::class,
			    ['logger' => $container->get('logger')->createRotatingLogger('queue')]
		    );
		})->alias('queue.worker', Worker::class);

		// Failer
		$container->share(QueueFailerInterface::class, function (Container $container)
		{
			return $container->get('queue.manager')->createFailer();
		})->alias('queue.failer', QueueFailerInterface::class);
	}
}
