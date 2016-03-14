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
use Windwalker\Core\Event\DispatcherAwareStaticInterface;
use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Data\Data;
use Windwalker\Event\Dispatcher;
use Windwalker\Event\Event;
use Windwalker\Event\EventInterface;
use Windwalker\Registry\Registry;

/**
 * The User class.
 *
 * @see \Windwalker\Authentication\Authentication
 *
 * @method static boolean          authenticate()   authenticate(Credential $credential)
 * @method static integer[]        getResults()     getResults()
 * @method static Credential       getCredential()  getCredential()
 * @method static MethodInterface  getMethod()      getMethod($name)
 * @method static Authentication   removeMethod()   removeMethod($name)
 * @method static Authentication   addMethod()      addMethod($name, MethodInterface $method)
 *
 * @since  2.0
 */
class User extends AbstractProxyFacade implements DispatcherAwareStaticInterface
{
	/**
	 * Property key.
	 *
	 * @var  string
	 */
	protected static $_key = 'system.authenticate';

	/**
	 * Property handler.
	 *
	 * @var UserHandlerInterface
	 */
	protected static $handler;

	/**
	 * Property dispatcher.
	 *
	 * @var Dispatcher
	 */
	protected static $dispatcher;

	/**
	 * login
	 *
	 * @param array|object $user
	 * @param bool         $remember
	 * @param array        $options
	 *
	 * @return  boolean
	 */
	public static function login($user, $remember = false, $options = array())
	{
		if (!is_array($user) && !is_object($user))
		{
			throw new \InvalidArgumentException('Credential should be array or object.');
		}

		if (!($user instanceof Credential))
		{
			$user = new Credential($user);
		}

		$options = $options instanceof Registry ? $options : new Registry($options);

		$options['remember'] = $remember;

		// Before login event
		$event = static::triggerEvent('onUserBeforeLogin', array('user' => &$user, 'options' => &$options));

		// Do login
		if ($result = static::authenticate($event['user']))
		{
			$user = static::getCredential();

			// Authorise event
			static::triggerEvent('onUserAuthorisation', array(
				'user'    => &$user,
				'options' => &$options,
				'result'  => &$result
			));

			if ($result)
			{
				$result = static::makeUserLogin($user);
			}
		}

		// After login event
		static::triggerEvent('onUserAfterLogin', array(
			'user'    => $user,
			'options' => $options,
			'result'  => &$result
		));

		// Fail event
		if (!$result)
		{
			static::triggerEvent('onUserLoginFailure', array('user' => $user, 'options' => $options, 'results' => static::getResults()));

			return false;
		}

		return true;
	}

	/**
	 * makeUserLogin
	 *
	 * @param mixed $user
	 *
	 * @return  boolean
	 */
	public static function makeUserLogin($user)
	{
		if (!is_array($user) && !is_object($user))
		{
			$user = User::get($user);
		}
		elseif (!($user instanceof UserDataInterface))
		{
			$user = new UserData($user);
		}

		if ($user->isNull())
		{
			return false;
		}

		return static::getHandler()->login($user);
	}

	/**
	 * logout
	 *
	 * @param array|object $conditions
	 * @param array        $options
	 *
	 * @return  boolean
	 */
	public static function logout($conditions = array(), $options = array())
	{
		$options = $options instanceof Registry ? $options : new Registry($options);

		$user = User::get($conditions);

		// Before logout event
		$event = static::triggerEvent('onUserBeforeLogout', array('user' => $user, 'conditions' => &$conditions, 'options' => &$options));

		// Do logout
		$result = static::getHandler()->logout($event['user']);

		// After logout event
		$event = static::triggerEvent('onUserAfterLogout', array(
			'user'       => $user,
			'conditions' => $conditions,
			'options'    => &$options,
			'result'     => &$result
		));

		// Fail event
		if (!$event['result'])
		{
			static::triggerEvent('onUserLogoutFailure', array('user' => $user, 'conditions' => $conditions, 'options' => &$options));

			return false;
		}

		return true;
	}

	/**
	 * getUser
	 *
	 * @param array $conditions
	 *
	 * @return  UserDataInterface
	 */
	public static function get($conditions = array())
	{
		$user = static::getHandler()->load($conditions);

		if (!($user instanceof UserDataInterface))
		{
			throw new \UnexpectedValueException('User data should implement from UserDataInterface');
		}

		return $user;
	}

	/**
	 * save
	 *
	 * @param array $user
	 * @param array $options
	 *
	 * @throws \Exception
	 * @return  UserData
	 */
	public static function save($user = array(), $options = array())
	{
		if (!is_array($user) && !is_object($user))
		{
			throw new \InvalidArgumentException('User data should be array or object.');
		}

		if (!($user instanceof UserData))
		{
			$user = new UserData($user);
		}

		$options = ($options instanceof Registry) ? $options : new Registry($options);

		static::triggerEvent('onUserBeforeSave', array('user' => $user, 'options' => &$options));

		try
		{
			static::getHandler()->save($user);
		}
		catch (\Exception $e)
		{
			static::triggerEvent('onUserSaveFailure', array('user' => $user, 'exception' => $e, 'options' => $options));

			throw $e;
		}

		static::triggerEvent('onUserAfterSave', array('user' => $user, 'options' => $options));

		return $user;
	}

	/**
	 * delete
	 *
	 * @param mixed $conditions
	 * @param array $options
	 *
	 * @return  boolean
	 */
	public function delete($conditions = array(), $options = array())
	{
		if (is_object($conditions))
		{
			$conditions = get_object_vars($conditions);
		}

		if (!is_array($conditions))
		{
			$conditions = array('id' => $conditions);
		}

		$options = ($options instanceof Registry) ? $options : new Registry($options);

		static::triggerEvent('onUserBeforeDelete', array('conditions' => &$conditions, 'options' => &$options));

		try
		{
			static::$handler->delete($conditions);
		}
		catch (\Exception $e)
		{
			static::triggerEvent('onUserDeleteFailure', array('conditions' => $conditions, 'exception' => $e, 'options' => $options));

			return false;
		}

		static::triggerEvent('onUseAfterDelete', array('conditions' => $conditions, 'options' => $options));

		return true;
	}

	/**
	 * Method to get property Handler
	 *
	 * @return  UserHandlerInterface
	 */
	public static function getHandler()
	{
		if (!static::$handler)
		{
			throw new \LogicException('No User handler.');
		}

		return static::$handler;
	}

	/**
	 * Method to set property handler
	 *
	 * @param   UserHandlerInterface $handler
	 *
	 * @return  void
	 */
	public static function setHandler(UserHandlerInterface $handler)
	{
		static::$handler = $handler;
	}

	/**
	 * hasHandler
	 *
	 * @return  boolean
	 */
	public static function hasHandler()
	{
		return !empty(static::$handler);
	}

	/**
	 * triggerEvent
	 *
	 * @param string|\Windwalker\Event\Event $event
	 * @param array                          $args
	 *
	 * @return  EventInterface|Event
	 */
	public static function triggerEvent($event, $args = array())
	{
		if (!static::$dispatcher)
		{
			static::$dispatcher = static::getContainer()->get('system.dispatcher');
		}

		return static::$dispatcher->triggerEvent($event, $args);
	}
}
