<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

use Windwalker\Core\Config\Config;
use Windwalker\Core\Queue\Driver\QueueDriverInterface;
use Windwalker\Core\Queue\Driver\SqsQueueDriver;

/**
 * The QueueDriverFactory class.
 *
 * @since  __DEPLOY_VERSION__
 */
class QueueFactory
{
	/**
	 * Property config.
	 *
	 * @var  Config
	 */
	protected $config;

	/**
	 * Property drivers.
	 *
	 * @var  QueueDriverInterface[]
	 */
	protected $drivers = [];

	/**
	 * QueueDriverFactory constructor.
	 *
	 * @param Config $config
	 */
	public function __construct(Config $config)
	{
		$this->config = $config;
	}

	/**
	 * create
	 *
	 * @param string $driver
	 *
	 * @return  QueueManager
	 */
	public function create($driver = 'sync')
	{
		return new QueueManager($this->getDriver($driver));
	}

	/**
	 * getDriver
	 *
	 * @param string $driver
	 *
	 * @return  QueueDriverInterface
	 */
	public function getDriver($driver = null)
	{
		if ($driver === null)
		{
			$driver = $this->config->get('queue.driver');
		}

		$driver = strtolower($driver);

		if (!isset($this->drivers[$driver]))
		{
			$this->drivers[$driver] = $this->createDriver($driver);
		}

		return $this->drivers[$driver];
	}

	/**
	 * create
	 *
	 * @param string $driver
	 *
	 * @return  QueueDriverInterface
	 */
	public function createDriver($driver = null)
	{
		if ($driver === null)
		{
			$driver = $this->config->get('queue.driver');
		}

		$driver = strtolower($driver);

		$queueConfig = $this->config->extract('queue.' . $driver);

		if (!$queueConfig->toArray())
		{
			throw new \LogicException('No queue config for ' . $driver);
		}

		switch ($driver)
		{
			case 'sqs':
				return new SqsQueueDriver(
					$queueConfig->get('key'),
					$queueConfig->get('secret'),
					$queueConfig->get('default', 'default'),
					[
						'region' => $queueConfig->get('region', 'us-west-2'),
						'version' => $queueConfig->get('version', 'latest')
					]
				);

			case 'sync':
			case 'database':
			case 'ironmq':
			case 'redis':
		}
	}
}
