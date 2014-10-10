<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Auth;

use Windwalker\Authenticate\Authenticate;
use Windwalker\Authenticate\Credential;
use Windwalker\Authenticate\Method\MethodInterface;
use Windwalker\Core\Facade\Facade;
use Windwalker\Core\Ioc;
use Windwalker\Data\Data;
use Windwalker\Event\Event;

/**
 * The User class.
 *
 * @method boolean          authenticate()   authenticate(Credential $credential)
 * @method integer[]        getResults()     getResults()
 * @method Credential       getCredential()  getCredential()
 * @method MethodInterface  getMethod()      getMethod($name)
 * @method Authenticate     removeMethod()   removeMethod($name)
 * @method Authenticate     addMethod()      addMethod($name, MethodInterface $method)
 *
 * @since  {DEPLOY_VERSION}
 */
class User extends Facade
{
	/**
	 * Property key.
	 *
	 * @var  string
	 */
	protected static $key = 'system.authenticate';

	/**
	 * login
	 *
	 * @param array|object $credential
	 *
	 * @return  boolean
	 */
	public static function login($credential)
	{
		if (!is_array($credential) || !is_object($credential))
		{
			throw new \InvalidArgumentException('Credential should be array or object.');
		}

		if (($credential instanceof Credential))
		{
			$credential = new Credential($credential);
		}

		$dispatcher = Ioc::getDispatcher();

		$dispatcher->triggerEvent('onUserBeforeLogin', array('credential' => $credential));

		if (!static::authenticate($credential))
		{
			return false;
		}

		$user = static::getCredential();

		$session = Ioc::getSession();

		$session->set('user', (array) $user);

		$dispatcher->triggerEvent('onUserAfterLogin', array('credential' => $user));

		return true;
	}

	/**
	 * logout
	 *
	 * @return  boolean
	 */
	public static function logout()
	{
		$session = Ioc::getSession();

		$session->clear('user');

		return true;
	}

	/**
	 * getUser
	 *
	 * @param array $conditions
	 *
	 * @return  mixed|Data
	 */
	public static function get($conditions = array())
	{
		if (is_object($conditions))
		{
			$conditions = get_object_vars($conditions);
		}

		if (!is_array($conditions))
		{
			$conditions = array('id' => $conditions);
		}

		$event = new Event('onUserLoad');

		$event['conditions'] = $conditions;

		Ioc::getDispatcher()->triggerEvent($event);

		$user = $event['user'];

		if (!$user)
		{
			return new Data;
		}

		return new Data($user);
	}

	public function save($user = array())
	{
		if (!is_array($user) || !is_object($user))
		{
			throw new \InvalidArgumentException('User data should be array or object.');
		}

		$user = new Data($user);

		$event = new Event('onUserSave');

		$event['user'] = $user;

		Ioc::getDispatcher()->triggerEvent($event);


	}
}
 