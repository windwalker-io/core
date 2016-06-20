<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\User;

use Windwalker\Authentication\Authentication;
use Windwalker\Authentication\AuthenticationInterface;
use Windwalker\Authentication\Credential;
use Windwalker\Authentication\Method\MethodInterface;
use Windwalker\Authorisation\Authorisation;
use Windwalker\Authorisation\AuthorisationInterface;
use Windwalker\Authorisation\PolicyInterface;
use Windwalker\Authorisation\PolicyProviderInterface;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\DispatcherAwareTrait;
use Windwalker\Event\EventTriggerableInterface;
use Windwalker\Registry\Registry;

/**
 * The UserManager class.
 *
 * @since  {DEPLOY_VERSION}
 */
class UserManager implements EventTriggerableInterface, DispatcherAwareInterface
{
	use DispatcherAwareTrait;

	/**
	 * Property handler.
	 *
	 * @var  UserHandlerInterface
	 */
	protected $handler;
	/**
	 * Property authentication.
	 *
	 * @var  AuthenticationInterface
	 */
	private $authentication;
	/**
	 * Property authorisation.
	 *
	 * @var  AuthorisationInterface
	 */
	private $authorisation;

	/**
	 * UserManager constructor.
	 *
	 * @param UserHandlerInterface    $handler
	 * @param AuthenticationInterface $authentication
	 * @param AuthorisationInterface  $authorisation
	 */
	public function __construct(UserHandlerInterface $handler = null, AuthenticationInterface $authentication = null,
		AuthorisationInterface $authorisation = null)
	{
		$this->handler = $handler ? : new NullUserHandler;
		$this->authentication = $authentication ? : new Authentication;
		$this->authorisation = $authorisation ? : new Authorisation;
	}

	/**
	 * authorise
	 *
	 * @param string            $policy
	 * @param UserDataInterface $user
	 * @param mixed             ...$data
	 *
	 * @return  bool
	 */
	public function authorise($policy, UserDataInterface $user, ...$data)
	{
		return $this->getAuthorisation()->authorise($policy, $user, ...$data);
	}

	/**
	 * authenticate
	 *
	 * @param Credential $credential
	 *
	 * @return  bool|Credential
	 */
	public function authenticate(Credential $credential)
	{
		return $this->getAuthentication()->authenticate($credential);
	}

	/**
	 * Method to get property Results
	 *
	 * @return  array
	 */
	public function getAuthResults()
	{
		return $this->getAuthentication()->getResults();
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
		if ($result = $this->authentication->authenticate($event['user']))
		{
			$user = $this->authentication->getCredential();

			// Authorise event
			$this->triggerEvent('onUserAuthorisation', [
				'user'    => &$user,
				'authorisation' => $this->getAuthorisation(),
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
	 * Alias of getUser().
	 *
	 * @param array $conditions
	 *
	 * @return  UserDataInterface
	 */
	public function get($conditions = array())
	{
		return $this->getUser($conditions);
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
	 * addPolicy
	 *
	 * @param   string                    $name
	 * @param   callable|PolicyInterface  $handler
	 *
	 * @return  static
	 */
	public function addPolicy($name, $handler)
	{
		$this->getAuthorisation()->addPolicy($name, $handler);

		return $this;
	}

	/**
	 * registerPolicyProvider
	 *
	 * @param PolicyProviderInterface $provider
	 *
	 * @return  static
	 */
	public function registerPolicyProvider(PolicyProviderInterface $provider)
	{
		$this->getAuthorisation()->registerPolicyProvider($provider);

		return $this;
	}

	/**
	 * addAuthMethod
	 *
	 * @param string          $name
	 * @param MethodInterface $method
	 *
	 * @return  static
	 */
	public function addAuthMethod($name, MethodInterface $method)
	{
		$this->getAuthentication()->addMethod($name, $method);

		return $this;
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
	 * @return  static
	 */
	public function setHandler(UserHandlerInterface $handler)
	{
		$this->handler = $handler;
		
		return $this;
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

	/**
	 * Method to get property Authorisation
	 *
	 * @return  AuthorisationInterface
	 */
	public function getAuthorisation()
	{
		return $this->authorisation;
	}

	/**
	 * Method to set property authorisation
	 *
	 * @param   AuthorisationInterface $authorisation
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setAuthorisation($authorisation)
	{
		$this->authorisation = $authorisation;

		return $this;
	}

	/**
	 * Method to get property Authentication
	 *
	 * @return  AuthenticationInterface
	 */
	public function getAuthentication()
	{
		return $this->authentication;
	}

	/**
	 * Method to set property authentication
	 *
	 * @param   AuthenticationInterface $authentication
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setAuthentication($authentication)
	{
		$this->authentication = $authentication;

		return $this;
	}
}
