<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Authentication;

/**
 * The NullUserHandler class.
 *
 * @since  {DEPLOY_VERSION}
 */
class NullUserHandler implements UserHandlerInterface
{
	/**
	 * load
	 *
	 * @param array $conditions
	 *
	 * @return  mixed|false
	 */
	public function load($conditions)
	{
		return new UserData;
	}

	/**
	 * save
	 *
	 * @param UserDataInterface $user
	 *
	 * @return  UserDataInterface
	 */
	public function save(UserDataInterface $user)
	{
		return $user;
	}

	/**
	 * delete
	 *
	 * @param UserDataInterface $user
	 *
	 * @return  boolean
	 */
	public function delete(UserDataInterface $user)
	{
		return $user;
	}

	/**
	 * login
	 *
	 * @param UserDataInterface $user
	 *
	 * @return  boolean
	 */
	public function login(UserDataInterface $user)
	{
		return false;
	}

	/**
	 * logout
	 *
	 * @param UserDataInterface $user
	 *
	 * @return bool
	 */
	public function logout(UserDataInterface $user)
	{
		return true;
	}
}
