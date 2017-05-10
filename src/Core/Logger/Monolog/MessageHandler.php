<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Logger\Monolog;

use Monolog\Handler\AbstractHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Windwalker\Core\Application\WindwalkerApplicationInterface;
use Windwalker\Core\Frontend\Bootstrap;

/**
 * The MessageHandler class.
 *
 * @since  __DEPLOY_VERSION__
 */
class MessageHandler extends AbstractHandler
{
	/**
	 * Property app.
	 *
	 * @var  WindwalkerApplicationInterface
	 */
	protected $app;

	/**
	 * Property types.
	 *
	 * @var  array
	 */
	protected $types = [
		LogLevel::DEBUG     => Bootstrap::MSG_INFO,
		LogLevel::INFO      => Bootstrap::MSG_INFO,
		LogLevel::NOTICE    => Bootstrap::MSG_WARNING,
		LogLevel::WARNING   => Bootstrap::MSG_WARNING,
		LogLevel::ERROR     => Bootstrap::MSG_DANGER,
		LogLevel::CRITICAL  => Bootstrap::MSG_DANGER,
		LogLevel::ALERT     => Bootstrap::MSG_DANGER,
		LogLevel::EMERGENCY => Bootstrap::MSG_DANGER,
	];

	/**
	 * Class init.
	 *
	 * @param WindwalkerApplicationInterface $app    The application.
	 * @param bool|int                       $level  The minimum logging level at which this handler will be triggered
	 * @param Boolean                        $bubble Whether the messages that are handled can bubble up the stack or not
	 */
	public function __construct(WindwalkerApplicationInterface $app, $level = Logger::DEBUG, $bubble = true)
	{
		parent::__construct($level, $bubble);

		$this->app = $app;
	}

	/**
	 * Handles a record.
	 *
	 * All records may be passed to this method, and the handler should discard
	 * those that it does not want to handle.
	 *
	 * The return value of this function controls the bubbling process of the handler stack.
	 * Unless the bubbling is interrupted (by returning true), the Logger class will keep on
	 * calling further handlers in the stack with a given log record.
	 *
	 * @param  array $record The record to handle
	 *
	 * @return Boolean true means that this handler handled the record, and that bubbling is not permitted.
	 *                        false means the record was either not processed or that this handler allows bubbling.
	 */
	public function handle(array $record)
	{
		if (!$this->isHandling($record))
		{
			return false;
		}

		$type = $this->types[strtolower($record['level_name'])];

		$this->app->addMessage($record['message'], $type);

		return false === $this->bubble;
	}
}
