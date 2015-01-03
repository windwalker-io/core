<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Object;

/**
 * The NullObject class.
 * 
 * @since  2.0
 */
class NullObject implements NullObjectInterface
{
	/**
	 * Is this object not contain any values.
	 *
	 * @return boolean
	 */
	public function isNull()
	{
		return true;
	}

	/**
	 * Is this object not contain any values.
	 *
	 * @return  boolean
	 */
	public function notNull()
	{
		return false;
	}

	/**
	 * __get
	 *
	 * @param $name
	 *
	 * @return  mixed
	 */
	public function __get($name)
	{
		return null;
	}

	/**
	 * __set
	 *
	 * @param $name
	 * @param $value
	 *
	 * @return mixed
	 */
	public function __set($name, $value)
	{
		return;
	}

	/**
	 * __isset
	 *
	 * @param $name
	 *
	 * @return  mixed
	 */
	public function __isset($name)
	{
		return false;
	}

	/**
	 * __toString
	 *
	 * @return  mixed
	 */
	public function __toString()
	{
		return null;
	}

	/**
	 * __unset
	 *
	 * @param $name
	 *
	 * @return  mixed
	 */
	public function __unset($name)
	{
		return;
	}

	/**
	 * __call
	 *
	 * @param $name
	 * @param $args
	 *
	 * @return  mixed
	 */
	public function __call($name, $args)
	{
		return null;
	}
}
