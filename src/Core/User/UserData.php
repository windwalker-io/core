<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\User;

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
}
