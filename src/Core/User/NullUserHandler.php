<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\User;

/**
 * The NullUserHandler class.
 *
 * @since  2.1.7.1
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
        return new UserData();
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
     * @param array $conditions
     *
     * @return boolean
     */
    public function delete($conditions)
    {
        return true;
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
    public function logout(UserDataInterface $user = null)
    {
        return true;
    }
}
