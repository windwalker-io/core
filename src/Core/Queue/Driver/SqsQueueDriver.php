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
use Windwalker\Core\Queue\MessageResponse;

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
	 * @var SqsClient
	 */
	protected $client;

	/**
	 * Property name.
	 *
	 * @var string
	 */
	protected $default;

	/**
	 * SqsQueueDriver constructor.
	 *
	 * @param string $default
	 */
	public function __construct($default)
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

		$this->default = $default;
	}

	/**
	 * push
	 *
	 * @param string $body
	 * @param string $queue
	 * @param int    $delay
	 * @param array  $options
	 *
	 * @return string|int
	 */
	public function push($body, $queue = null, $delay = 0, array $options = [])
	{
		$message = [
			'QueueUrl' => $this->getQueueUrl($queue),
			'MessageBody' => $body
		];

		if (is_int($delay) && $delay > 0)
		{
			$message['DelaySeconds'] = $delay;
		}

		$message = array_merge($message, $options);

		return $this->client->sendMessage($message)->get('MessageId');
	}

	/**
	 * pop
	 *
	 * @param string $queue
	 *
	 * @return MessageResponse|bool
	 */
	public function pop($queue = null)
	{
		$result = $this->client->receiveMessage([
			'QueueUrl' => $this->getQueueUrl($queue),
			'AttributeNames' => ['ApproximateReceiveCount'],
		]);

		if ($result['Messages'] === null)
		{
			return false;
		}

		$data = $result['Messages'][0];

		$res = new MessageResponse($result['Messages'][0]);

		$res->setId($data['MessageId']);
		$res->setAttempts($data['Attributes']['ApproximateReceiveCount']);
		$res->setBody(json_decode($data['Body']));
		$res->setRawData($data['Body']);
		$res->setQueue($queue ? : $this->default);

		return $res;
	}

	/**
	 * delete
	 *
	 * @param MessageResponse|string $message
	 * @param null                   $queue
	 *
	 * @return static
	 */
	public function delete($message, $queue = null)
	{
		if ($message instanceof MessageResponse)
		{
			$queue = $message->getQueue();
			$message = $message->get('ReceiptHandle');
		}

		$this->client->deleteMessage([
			'QueueUrl' => $this->getQueueUrl($queue),
			'ReceiptHandle' => $message
		]);

		return $this;
	}

	/**
	 * release
	 *
	 * @param MessageResponse|string $message
	 * @param int                    $delay
	 * @param string                 $queue
	 *
	 * @return  static
	 */
	public function release($message, $delay = 0, $queue = null)
	{
		if ($message instanceof MessageResponse)
		{
			$queue = $message->getQueue();
			$message = $message->get('ReceiptHandle');
		}

		$this->client->changeMessageVisibility([
			'QueueUrl' => $this->getQueueUrl($queue),
			'ReceiptHandle' => $message,
			'VisibilityTimeout' => $delay
		]);

		return $this;
	}

	/**
	 * getQueueUrl
	 *
	 * @param string $queue
	 *
	 * @return string
	 */
	public function getQueueUrl($queue = null)
	{
		$queue = $queue ? : $this->default;

		if (filter_var($queue, FILTER_VALIDATE_URL) !== false)
		{
			return $queue;
		}

		return $this->client->getQueueUrl(array('QueueName' => $queue))->get('QueueUrl');
	}
}
