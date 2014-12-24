<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Authenticate;

/**
 * The UserDataInterface class.
 * 
 * @since  {DEPLOY_VERSION}
 */
interface UserDataInterface
{
	/**
	 * isGuest
	 *
	 * @return  boolean
	 */
	public function isGuest();

	/**
	 * isMember
	 *
	 * @return  boolean
	 */
	public function isMember();

	/**
	 * toArray
	 *
	 * @return  array
	 */
	public function dump();
}
