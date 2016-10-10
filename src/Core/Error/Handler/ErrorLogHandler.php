<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Error\Handler;

use Windwalker\Core\Logger\Logger;

/**
 * The ErrorLogHandler class.
 *
 * @since  3.0
 */
class ErrorLogHandler implements ErrorHandlerInterface
{
	/**
	 * __invoke
	 *
	 * @param  \Exception|\Throwable $e
	 *
	 * @return  void
	 */
	public function __invoke($e)
	{
		Logger::error('error', $e->getMessage(), ['code' => $e->getCode()]);
	}
}
