<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Authenticate;

use Windwalker\Data\Data;

/**
 * The UserData class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class UserData extends Data implements UserDataInterface
{
	/**
	 * isLogin
	 *
	 * @return  boolean
	 */
	public function isLogin()
	{
		return (bool) $this->id;
	}

	/**
	 * notLogin
	 *
	 * @return  boolean
	 */
	public function notLogin()
	{
		return !$this->id;
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
