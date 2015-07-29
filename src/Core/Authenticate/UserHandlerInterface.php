<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Authenticate;

use Windwalker\Data\Data;

/**
 * The UserHandlerInterface class.
 * 
 * @since  2.0
 */
interface UserHandlerInterface
{
	/**
	 * load
	 *
	 * @param array $conditions
	 *
	 * @return  mixed|false
	 */
	public function load($conditions);

	/**
	 * save
	 *
	 * @param UserDataInterface $user
	 *
	 * @return  UserDataInterface
	 */
	public function save(UserDataInterface $user);

	/**
	 * delete
	 *
	 * @param UserDataInterface $user
	 *
	 * @return  boolean
	 */
	public function delete(UserDataInterface $user);

	/**
	 * login
	 *
	 * @param UserDataInterface $user
	 *
	 * @return  boolean
	 */
	public function login(UserDataInterface $user);

	/**
	 * logout
	 *
	 * @param UserDataInterface $user
	 *
	 * @return bool
	 */
	public function logout(UserDataInterface $user);
}
