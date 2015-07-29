<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT Taiwan, Inc. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Model\Exception;

/**
 * Class ValidFailException
 *
 * @since 1.0
 */
class ValidFailException extends \Exception
{
	/**
	 * Class init.
	 *
	 * @param string     $message
	 * @param int        $code
	 * @param \Exception $previous
	 */
	public function __construct($message = "", $code = 0, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
 