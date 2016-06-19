<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Authentication;

use Windwalker\Authentication\Authentication;
use Windwalker\Authentication\Credential;
use Windwalker\Authentication\Method\MethodInterface;
use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The User class.
 *
 * @see \Windwalker\Core\Authentication\UserManager
 *
 * @method static boolean          authenticate(Credential $credential)
 * @method static integer[]        getResults()
 * @method static Credential       getCredential()
 * @method static MethodInterface  getMethod($name)
 * @method static Authentication   removeMethod($name)
 * @method static UserManager      addMethod($name, MethodInterface $method)
 * @method static UserManager      setHandler(Credential $credential)
 * @method static boolean          hasHandler()
 * @method static UserHandlerInterface  getHandler(Credential $credential)
 * @method static boolean          login($user, $remember = false, $options = array())
 * @method static boolean          makeUserLogin($user)
 * @method static boolean          logout($conditions = [], $options = [])
 * @method static UserDataInterface  getUser($conditions = array())
 * @method static UserDataInterface  save($user = array(), $options = array())
 * @method static boolean          delete($conditions = null, $options = [])
 *
 * @since  2.0
 */
class User extends AbstractProxyFacade
{
	/**
	 * Property key.
	 *
	 * @var  string
	 */
	protected static $_key = 'system.user.manager';
}
