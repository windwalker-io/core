<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Logger;

use Psr\Log\LoggerInterface;
use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The Logger class.
 *
 * @method  static  LoggerPool  emergency($category, $message, array $context = array())
 * @method  static  LoggerPool  alert($category, $message, array $context = array())
 * @method  static  LoggerPool  critical($category, $message, array $context = array())
 * @method  static  LoggerPool  error($category, $message, array $context = array())
 * @method  static  LoggerPool  warning($category, $message, array $context = array())
 * @method  static  LoggerPool  notice($category, $message, array $context = array())
 * @method  static  LoggerPool  info($category, $message, array $context = array())
 * @method  static  LoggerPool  debug($category, $message, array $context = array())
 * @method  static  LoggerPool  log($category, $level, $message, array $context = array())
 * @method  static  LoggerPool  addLogger($category, LoggerInterface $logger)
 * @method  static  boolean     hasLogger($category)
 * @method  static  LoggerPool  removeLogger($category)
 * @method  static  LoggerPool  setLoggers(array $loggers)
 * @method  static  LoggerInterface    getLogger($category)
 * @method  static  LoggerInterface[]  getLoggers()
 *
 * @since  2.1.1
 */
class Logger extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'system.logger';
}
