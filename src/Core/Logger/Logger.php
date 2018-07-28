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
 * @see    LoggerManager
 *
 * @method  static LoggerManager  emergency($channel, $message, array $context = [])
 * @method  static LoggerManager  alert($channel, $message, array $context = [])
 * @method  static LoggerManager  critical($channel, $message, array $context = [])
 * @method  static LoggerManager  error($channel, $message, array $context = [])
 * @method  static LoggerManager  warning($channel, $message, array $context = [])
 * @method  static LoggerManager  notice($channel, $message, array $context = [])
 * @method  static LoggerManager  info($channel, $message, array $context = [])
 * @method  static LoggerManager  debug($channel, $message, array $context = [])
 * @method  static LoggerManager  log($channel, $level, $message, array $context = [])
 * @method  static LoggerManager  addLogger($channel, LoggerInterface $logger)
 * @method  static Monolog        createLogger($channel, $level = Logger::DEBUG, HandlerInterface $handler = null)
 * @method  static boolean        hasLogger($channel)
 * @method  static LoggerManager  removeLogger($channel)
 * @method  static LoggerManager  setLoggers(array $loggers)
 * @method  static LoggerInterface    getLogger($channel, $level = Logger::DEBUG)
 * @method  static LoggerInterface[]  getLoggers()
 * @method  static LoggerInterface    createChannel($channel, $level = Logger::DEBUG)
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
     * phpcs:disable
    */
    protected static $_key = 'logger';
    // phpcs:enable
}
