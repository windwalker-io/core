<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Logger;

use Psr\Log\LoggerInterface;
use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The Logger class.
 *
 * @see LoggerManager
 *
 * @method  static  LoggerManager  emergency($category, $message, array $context = array())
 * @method  static  LoggerManager  alert($category, $message, array $context = array())
 * @method  static  LoggerManager  critical($category, $message, array $context = array())
 * @method  static  LoggerManager  error($category, $message, array $context = array())
 * @method  static  LoggerManager  warning($category, $message, array $context = array())
 * @method  static  LoggerManager  notice($category, $message, array $context = array())
 * @method  static  LoggerManager  info($category, $message, array $context = array())
 * @method  static  LoggerManager  debug($category, $message, array $context = array())
 * @method  static  LoggerManager  log($category, $level, $message, array $context = array())
 * @method  static  LoggerManager  addLogger($category, LoggerInterface $logger)
 * @method  static  boolean     hasLogger($category)
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
	const DEBUG = 100;

	/**
	 * Interesting events
	 *
	 * Examples: User logs in, SQL logs.
	 */
	const INFO = 200;

	/**
	 * Uncommon events
	 */
	const NOTICE = 250;

	/**
	 * Exceptional occurrences that are not errors
	 *
	 * Examples: Use of deprecated APIs, poor use of an API,
	 * undesirable things that are not necessarily wrong.
	 */
	const WARNING = 300;

	/**
	 * Runtime errors
	 */
	const ERROR = 400;

	/**
	 * Critical conditions
	 *
	 * Example: Application component unavailable, unexpected exception.
	 */
	const CRITICAL = 500;

	/**
	 * Action must be taken immediately
	 *
	 * Example: Entire website down, database unavailable, etc.
	 * This should trigger the SMS alerts and wake you up.
	 */
	const ALERT = 550;

	/**
	 * Urgent alert.
	 */
	const EMERGENCY = 600;

	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'logger';
}
