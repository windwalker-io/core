<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Error\Handler;

use Windwalker\Core\Logger\LoggerManager;

/**
 * The ErrorLogHandler class.
 *
 * @since  3.0
 */
class ErrorLogHandler implements ErrorHandlerInterface
{
	/**
	 * Property manager.
	 *
	 * @var  LoggerManager
	 */
	protected $manager;

	/**
	 * ErrorLogHandler constructor.
	 *
	 * @param LoggerManager $manager
	 */
	public function __construct(LoggerManager $manager)
	{
		$this->manager = $manager;
	}

	/**
	 * __invoke
	 *
	 * @param  \Exception|\Throwable $e
	 *
	 * @return  void
	 */
	public function __invoke($e)
	{
		$this->manager->error('error', $e->getMessage(), ['code' => $e->getCode()]);
	}
}
