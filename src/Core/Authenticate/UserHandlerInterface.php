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
 * The UserHandlerInterface class.
 * 
 * @since  {DEPLOY_VERSION}
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
