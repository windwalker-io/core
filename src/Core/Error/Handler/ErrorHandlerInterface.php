<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Error\Handler;

/**
 * The ErrorHandlerInterface class.
 *
 * @since  3.0
 */
interface ErrorHandlerInterface
{
	/**
	 * __invoke
	 *
	 * @param  \Exception|\Throwable  $e
	 *
	 * @return  void
	 */
	public function __invoke($e);
}
