<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

/**
 * The QueueMessage class.
 *
 * @since  __DEPLOY_VERSION__
 */
class QueueMessage implements \JsonSerializable
{
	public $name = '';

	public $job = '';

	public $data = [];

	/**
	 * jsonSerialize
	 *
	 * @return  array
	 */
	public function jsonSerialize()
	{
		return get_object_vars($this);
	}
}
