<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Authentication;

use Windwalker\Authentication\Authentication;
use Windwalker\Authentication\Credential;
use Windwalker\Authentication\Method\MethodInterface;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\DispatcherAwareTrait;
use Windwalker\Event\EventTriggerableInterface;
use Windwalker\Registry\Registry;

/**
 * The UserManager class.
 *
 * @since  {DEPLOY_VERSION}
 */
class UserManager extends Authentication implements EventTriggerableInterface, DispatcherAwareInterface
{
	use DispatcherAwareTrait;

	/**
	 * Property handler.
	 *
	 * @var  UserHandlerInterface
	 */
	protected $handler;

	/**
	 * UserManager constructor.
	 *
	 * @param UserHandlerInterface $handler
	 * @param MethodInterface[]    $methods
	 */
	public function __construct(UserHandlerInterface $handler = null, array $methods = [])
	{
		$this->handler = $handler;
		
		parent::__construct($methods);
	}

	/**
	 * login
	 *
	 * @param array|object $user
	 * @param bool         $remember
	 * @param array        $options
	 *
	 * @return  boolean
	 */
	public function login($user, $remember = false, $options = array())
	{
		if (!is_array($user) && !is_object($user))
		{
			throw new \InvalidArgumentException('Credential should be array or object.');
		}

		if (!$user instanceof Credential)
		{
			$user = new Credential($user);
		}

		$options = $options instanceof Registry ? $options : new Registry($options);

		$options['remember'] = $remember;

		// Before login event
		$event = $this->triggerEvent('onUserBeforeLogin', ['user' => &$user, 'options' => &$options]);

		// Do login
		if ($result = $this->authenticate($event['user']))
		{
			$user = $this->getCredential();

			// Authorise event
			$this->triggerEvent('onUserAuthorisation', [
				'user'    => &$user,
				'options' => &$options,
				'result'  => &$result
			]);

			if ($result)
			{
				$result = $this->makeUserLogin($user);
			}
		}

		// After login event
		$this->triggerEvent('onUserAfterLogin', [
			'user'    => $user,
			'options' => $options,
			'result'  => &$result
		]);

		// Fail event
		if (!$result)
		{
			$this->triggerEvent('onUserLoginFailure', ['user' => $user, 'options' => $options, 'results' => $this->authentication->getResults()]);

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
	public function makeUserLogin($user)
	{
		if (!is_array($user) && !is_object($user))
		{
			$user = $this->getUser($user);
		}
		elseif (!($user instanceof UserDataInterface))
		{
			$user = new UserData($user);
		}

		if ($user->isNull())
		{
			return false;
		}

		return $this->getHandler()->login($user);
	}

	/**
	 * logout
	 *
	 * @param array|object $conditions
	 * @param array        $options
	 *
	 * @return  boolean
	 */
	public function logout($conditions = [], $options = [])
	{
		$options = $options instanceof Registry ? $options : new Registry($options);

		$user = $this->getUser($conditions);

		// Before logout event
		$event = $this->triggerEvent('onUserBeforeLogout', ['user' => $user, 'conditions' => &$conditions, 'options' => &$options]);

		// Do logout
		$result = $this->getHandler()->logout($event['user']);

		// After logout event
		$event = $this->triggerEvent('onUserAfterLogout', [
			'user'       => $user,
			'conditions' => $conditions,
			'options'    => &$options,
			'result'     => &$result
		]);

		// Fail event
		if (!$event['result'])
		{
			$this->triggerEvent('onUserLogoutFailure', ['user' => $user, 'conditions' => $conditions, 'options' => &$options]);

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
	public function getUser($conditions = array())
	{
		$user = $this->getHandler()->load($conditions);

		if (!$user instanceof UserDataInterface)
		{
			throw new \UnexpectedValueException('User data should be instance of ' . UserDataInterface::class);
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
	 * @return  UserDataInterface
	 */
	public function save($user = array(), $options = array())
	{
		if (!is_array($user) && !is_object($user))
		{
			throw new \InvalidArgumentException('User data should be array or object.');
		}

		if (!$user instanceof UserData)
		{
			$user = new UserData($user);
		}

		$options = ($options instanceof Registry) ? $options : new Registry($options);

		$this->triggerEvent('onUserBeforeSave', array('user' => $user, 'options' => &$options));

		try
		{
			$this->getHandler()->save($user);
		}
		catch (\Exception $e)
		{
			$this->triggerEvent('onUserSaveFailure', array('user' => $user, 'exception' => $e, 'options' => $options));

			throw $e;
		}

		$this->triggerEvent('onUserAfterSave', array('user' => $user, 'options' => $options));

		return $user;
	}

	/**
	 * delete
	 *
	 * @param array $conditions
	 * @param array $options
	 *
	 * @return  boolean
	 */
	public function delete($conditions = null, $options = [])
	{
		$options = ($options instanceof Registry) ? $options : new Registry($options);

		$this->triggerEvent('onUserBeforeDelete', array('conditions' => &$conditions, 'options' => &$options));

		try
		{
			$this->getHandler()->delete($conditions);
		}
		catch (\Exception $e)
		{
			$this->triggerEvent('onUserDeleteFailure', array('conditions' => $conditions, 'exception' => $e, 'options' => $options));

			return false;
		}

		$this->triggerEvent('onUseAfterDelete', array('conditions' => $conditions, 'options' => $options));

		return true;
	}

	/**
	 * Method to get property Handler
	 *
	 * @return  UserHandlerInterface
	 */
	public function getHandler()
	{
		if (!$this->handler)
		{
			throw new \LogicException('No User handler set in ' . __CLASS__);
		}

		return $this->handler;
	}

	/**
	 * Method to set property handler
	 *
	 * @param   UserHandlerInterface $handler
	 *
	 * @return  void
	 */
	public function setHandler(UserHandlerInterface $handler)
	{
		$this->handler = $handler;
	}

	/**
	 * hasHandler
	 *
	 * @return  boolean
	 */
	public function hasHandler()
	{
		return !empty($this->handler);
	}
}
