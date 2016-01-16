<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Authentication;

/**
 * The UserDataInterface class.
 * 
 * @since  2.0
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

	/**
	 * bind
	 *
	 * @param array $data
	 *
	 * @return  mixed
	 */
	public function bind($data);

	/**
	 * isNull
	 *
	 * @return  boolean
	 */
	public function isNull();

	/**
	 * notNull
	 *
	 * @return  boolean
	 */
	public function notNull();
}
