<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Logger;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger as Monolog;
use Psr\Log\LoggerInterface;
use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The Logger class.
 *
 * @see LoggerManager
 *
 * @method  static  LoggerManager  emergency($category, $message, array $context = [])
 * @method  static  LoggerManager  alert($category, $message, array $context = [])
 * @method  static  LoggerManager  critical($category, $message, array $context = [])
 * @method  static  LoggerManager  error($category, $message, array $context = [])
 * @method  static  LoggerManager  warning($category, $message, array $context = [])
 * @method  static  LoggerManager  notice($category, $message, array $context = [])
 * @method  static  LoggerManager  info($category, $message, array $context = [])
 * @method  static  LoggerManager  debug($category, $message, array $context = [])
 * @method  static  LoggerManager  log($category, $level, $message, array $context = [])
 * @method  static  LoggerManager  addLogger($category, LoggerInterface $logger)
 * @method  static  Monolog        createLogger($categoey, $level = Logger::DEBUG, HandlerInterface $handler = null)
 * @method  static  boolean        hasLogger($category)
 * @method  static  LoggerManager  removeLogger($category)
 * @method  static  LoggerManager  setLoggers(array $loggers)
 * @method  static  LoggerInterface    getLogger($category, $level = Logger::DEBUG)
 * @method  static  LoggerInterface[]  getLoggers()
 * @method  static  LoggerInterface    createCategory($category, $level = Logger::DEBUG)
 *
 * @since  2.1.1
 */
class Logger extends AbstractProxyFacade
{
	/**
	 * Detailed debug information
	 */
	const DEBUG = 'debug';

	/**
	 * Interesting events
	 *
	 * Examples: User logs in, SQL logs.
	 */
	const INFO = 'info';

	/**
	 * Uncommon events
	 */
	const NOTICE = 'notice';

	/**
	 * Exceptional occurrences that are not errors
	 *
	 * Examples: Use of deprecated APIs, poor use of an API,
	 * undesirable things that are not necessarily wrong.
	 */
	const WARNING = 'warning';

	/**
	 * Runtime errors
	 */
	const ERROR = 'error';

	/**
	 * Critical conditions
	 *
	 * Example: Application component unavailable, unexpected exception.
	 */
	const CRITICAL = 'critical';

	/**
	 * Action must be taken immediately
	 *
	 * Example: Entire website down, database unavailable, etc.
	 * This should trigger the SMS alerts and wake you up.
	 */
	const ALERT = 'alert';

	/**
	 * Urgent alert.
	 */
	const EMERGENCY = 'emergency';

	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'logger';
}
