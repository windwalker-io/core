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
use Windwalker\Core\Event\DispatcherAwareStaticInterface;
use Windwalker\Core\Facade\Facade;
use Windwalker\Core\Ioc;
use Windwalker\Data\Data;
use Windwalker\Event\Dispatcher;
use Windwalker\Event\Event;
use Windwalker\Registry\Registry;

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
class User extends Facade implements DispatcherAwareStaticInterface
{
	/**
	 * Property key.
	 *
	 * @var  string
	 */
	protected static $key = 'system.authenticate';

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
	 * @param array|object $credential
	 * @param array        $options
	 *
	 * @return  boolean
	 */
	public static function login($credential, $options = array())
	{
		if (!is_array($credential) || !is_object($credential))
		{
			throw new \InvalidArgumentException('Credential should be array or object.');
		}

		if (($credential instanceof Credential))
		{
			$credential = new Credential($credential);
		}

		$options = $options instanceof Registry ? $options : new Registry($options);

		static::triggerEvent('onUserBeforeLogin', array('credential' => $credential, 'options' => $options));

		if (!static::authenticate($credential))
		{
			return false;
		}

		$user = static::getCredential();

		$session = Ioc::getSession();

		$session->set('user', (array) $user);

		static::triggerEvent('onUserAfterLogin', array('credential' => $user, 'options' => $options));

		return true;
	}

	/**
	 * logout
	 *
	 * @param array $options
	 *
	 * @return  boolean
	 */
	public static function logout($credential, $options = array())
	{
		if (!is_array($credential) || !is_object($credential))
		{
			throw new \InvalidArgumentException('Credential should be array or object.');
		}

		if (($credential instanceof Credential))
		{
			$credential = new Credential($credential);
		}

		$options = $options instanceof Registry ? $options : new Registry($options);

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

		$user = static::$handler->load($conditions);

		if (!$user)
		{
			return new Data;
		}

		return new Data($user);
	}

	/**
	 * save
	 *
	 * @param array $user
	 * @param array $options
	 *
	 * @throws \Exception
	 * @return  Data
	 */
	public static function save($user = array(), $options = array())
	{
		if (!is_array($user) || !is_object($user))
		{
			throw new \InvalidArgumentException('User data should be array or object.');
		}

		if (!($user instanceof Data))
		{
			$user = new Data($user);
		}

		$options = ($options instanceof Registry) ? $options : new Registry($options);

		static::triggerEvent('onUserBeforeSave', array('user' => $user, 'options' => $options));

		$event = new Event('onUserAfterSave');

		$event['user'] = $user;
		$event['options'] = $options;
		$event['success'] = true;
		$event['message'] = null;

		try
		{
			static::$handler->save($user);
		}
		catch (\Exception $e)
		{
			$event['success'] = false;
			$event['message'] = $e->getMessage();
		}

		static::triggerEvent($event);

		if (!$event['success'])
		{
			throw new \Exception($event['message']);
		}

		return $user;
	}

	/**
	 * Method to get property Handler
	 *
	 * @return  UserHandlerInterface
	 */
	public static function getHandler()
	{
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
	 * triggerEvent
	 *
	 * @param string|\Windwalker\Event\Event $event
	 * @param array                          $args
	 *
	 * @return  mixed|void
	 */
	public static function triggerEvent($event, $args = array())
	{
		if (!static::$dispatcher)
		{
			static::$dispatcher = Ioc::getDispatcher();
		}

		static::$dispatcher->triggerEvent($event, $args);
	}
}
 