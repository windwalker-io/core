<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Authenticate;

use Windwalker\Data\Data;

/**
 * The UserData class.
 * 
 * @since  2.0
 */
class UserData extends Data implements UserDataInterface
{
	/**
	 * isLogin
	 *
	 * @return  boolean
	 */
	public function isGuest()
	{
		return !count($this);
	}

	/**
	 * notLogin
	 *
	 * @return  boolean
	 */
	public function isMember()
	{
		return !$this->isGuest();
	}

	/**
	 * toArray
	 *
	 * @return  array
	 */
	public function dump()
	{
		return iterator_to_array($this);
	}
}
