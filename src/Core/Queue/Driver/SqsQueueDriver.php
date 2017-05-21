<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Driver;

use Aws\Sqs\SqsClient;
use Windwalker\Core\Ioc;

/**
 * The SqsQueueDriver class.
 *
 * @since  __DEPLOY_VERSION__
 */
class SqsQueueDriver extends AbstractQueueDriver
{
	/**
	 * Property client.
	 *
	 * @var
	 */
	protected $client;

	/**
	 * Property name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * SqsQueueDriver constructor.
	 *
	 * @param string $name
	 */
	public function __construct($name)
	{
		$config = Ioc::getConfig();
		$this->client = new SqsClient([
			'region' => 'ap-northeast-1',
			'version' => 'latest',
			'credentials' => [
				'key'    => $config->get('queue.sqs.key'),
				'secret' => $config->get('queue.sqs.secret'),
			]
		]);

		$this->name = $name;
	}

	public function push($message)
	{
		$result = $this->client->sendMessage([
			'QueueUrl' => $this->getQueueUrl(),
			'MessageBody' => $message
		]);

		show($result);

		return $this;
	}

	public function getQueueUrl()
	{
		return $this->client->getQueueUrl(array('QueueName' => $this->name))->get('QueueUrl');
	}
}
