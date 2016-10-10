<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Response\Buffer;

/**
 * The JsonBuffer class.
 *
 * @since  3.0
 */
class JsonBuffer extends AbstractBuffer
{
	/**
	 * Method for sending the response in JSON format
	 *
	 * @return  string  The response in JSON format
	 */
	public function toString()
	{
		return json_encode(get_object_vars($this));
	}

	/**
	 * getMimeType
	 *
	 * @return  string
	 */
	public function getMimeType()
	{
		return 'application/json';
	}
}
