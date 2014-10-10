<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Authenticate;

use Windwalker\Authenticate\Authenticate;
use Windwalker\Authenticate\Credential;
use Windwalker\Authenticate\Method\MethodInterface;
use Windwalker\Core\Event\DispatcherAwareStaticInterface;
use Windwalker\Core\Facade\Facade;
use Windwalker\Core\Ioc;
use Windwalker\Data\Data;
use Windwalker\Event\Dispatcher;
use Windwalker\Registry\Registry;

/**
 * The User class.
 *
 * @method static boolean          authenticate()   authenticate(Credential $credential)
 * @method static integer[]        getResults()     getResults()
 * @method static Credential       getCredential()  getCredential()
 * @method static MethodInterface  getMethod()      getMethod($name)
 * @method static Authenticate     removeMethod()   removeMethod($name)
 * @method static Authenticate     addMethod()      addMethod($name, MethodInterface $method)
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
	 * @param bool         $remember
	 * @param array        $options
	 *
	 * @return  boolean
	 */
	public static function login($credential, $remember = false, $options = array())
	{
		if (!is_array($credential) && !is_object($credential))
		{
			throw new \InvalidArgumentException('Credential should be array or object.');
		}

		if (($credential instanceof Credential))
		{
			$credential = new Credential($credential);
		}

		$options = $options instanceof Registry ? $options : new Registry($options);

		$options['remember'] = $remember;

		static::triggerEvent('onUserBeforeLogin', array('credential' => $credential, 'options' => $options));

		if (!static::authenticate($credential))
		{
			static::triggerEvent('onUserLoginFailure', array('credential' => $credential, 'results' => static::getResults(), 'options' => $options));

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
	 * @param array|object $credential
	 * @param array        $options
	 *
	 * @return  boolean
	 */
	public static function logout($credential, $options = array())
	{
		if (!is_array($credential) && !is_object($credential))
		{
			throw new \InvalidArgumentException('Credential should be array or object.');
		}

		if (($credential instanceof Credential))
		{
			$credential = new Credential($credential);
		}

		$options = $options instanceof Registry ? $options : new Registry($options);

		static::triggerEvent('onUserBeforeLogout', array('credential' => $credential, 'options' => $options));

		$session = Ioc::getSession();

		$session->clear('user');

		static::triggerEvent('onUserAfterLogout', array('credential' => $credential, 'options' => $options));

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

		if (!$conditions)
		{
			$session = Ioc::getSession();

			$user = $session->get('user');
		}
		else
		{
			if (!is_array($conditions))
			{
				$conditions = array('id' => $conditions);
			}

			$user = static::$handler->load($conditions);
		}

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
		if (!is_array($user) && !is_object($user))
		{
			throw new \InvalidArgumentException('User data should be array or object.');
		}

		if (!($user instanceof Data))
		{
			$user = new Data($user);
		}

		$options = ($options instanceof Registry) ? $options : new Registry($options);

		static::triggerEvent('onUserBeforeSave', array('user' => $user, 'options' => $options));

		try
		{
			static::$handler->save($user);
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

		static::triggerEvent('onUserBeforeDelete', array('conditions' => $conditions, 'options' => $options));

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
 