<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Logger\Monolog;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;

/**
 * The MonologContainer class.
 *
 * @since  {DEPLOY_VERSION}
 */
abstract class GlobalContainer
{
	/**
	 * Property handlers.
	 *
	 * @var  HandlerInterface[]
	 */
	protected static $handlers = array();

	/**
	 * Property processors.
	 *
	 * @var  object[]
	 */
	protected static $processors = array();

	/**
	 * addHandler
	 *
	 * @param HandlerInterface $handler
	 *
	 * @return  void
	 */
	public static function addHandler(HandlerInterface $handler)
	{
		static::$handlers[] = $handler;
	}

	/**
	 * Method to get property Handlers
	 *
	 * @return  \Monolog\Handler\HandlerInterface[]
	 */
	public static function getHandlers()
	{
		return static::$handlers;
	}

	/**
	 * Method to set property handlers
	 *
	 * @param   \Monolog\Handler\HandlerInterface[] $handlers
	 *
	 * @return  void
	 */
	public static function setHandlers(array $handlers)
	{
		foreach ($handlers as $handler)
		{
			static::addHandler($handler);
		}
	}

	/**
	 * addProcessor
	 *
	 * @param object $processor
	 *
	 * @return  void
	 */
	public static function addProcessor($processor)
	{
		static::$processors[] = $processor;
	}

	/**
	 * Method to get property Processors
	 *
	 * @return  \object[]
	 */
	public static function getProcessors()
	{
		return static::$processors;
	}

	/**
	 * Method to set property processors
	 *
	 * @param   \object[] $processors
	 *
	 * @return  void
	 */
	public static function setProcessors(array $processors)
	{
		foreach ($processors as $processor)
		{
			static::addProcessor($processor);
		}
	}
}
