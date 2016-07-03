<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Security;

use Windwalker\Core\User\User;
use Windwalker\Core\Security\Exception\InvalidTokenException;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareInterface;
use Windwalker\Dom\HtmlElement;
use Windwalker\Filter\InputFilter;
use Windwalker\IO\Input;
use Windwalker\Session\Session;

/**
 * The CsrfManager class.
 *
 * @since  2.1.1
 */
class CsrfGuard implements ContainerAwareInterface
{
	const TOKEN_KEY = 'form.token';

	/**
	 * Property container.
	 *
	 * @var  Container
	 */
	protected $container;

	/**
	 * CsrfGuard constructor.
	 *
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Validate token or die.
	 *
	 * @param   bool    $justDie
	 * @param   string  $message
	 *
	 * @return  bool
	 */
	public function validate($justDie = false, $message = 'Invalid Token')
	{
		if (!static::checkToken())
		{
			if ($justDie)
			{
				exit($message);
			}

			throw new InvalidTokenException($message);
		}

		return true;
	}

	/**
	 * Create token input.
	 *
	 * @param   mixed  $userId
	 * @param   array  $attribs
	 *
	 * @return  HtmlElement
	 */
	public function input($userId = null, $attribs = array())
	{
		$attribs['type']  = 'hidden';
		$attribs['name']  = static::getFormToken($userId);
		$attribs['value'] = 1;

		return new HtmlElement('input', null, $attribs);
	}

	/**
	 * Create token string.
	 *
	 * @param   int  $length  Token string length.
	 *
	 * @return  string
	 */
	public function createToken($length = 12)
	{
		static $chars = '0123456789abcdef';

		$max   = strlen($chars) - 1;
		$token = '';
		$name  = session_name();

		for ($i = 0; $i < $length; ++$i)
		{
			$token .= $chars[(rand(0, $max))];
		}

		return md5($token . $name);
	}

	/**
	 * Get form token string.
	 *
	 * @param   boolean $forceNew Force create new token.
	 *
	 * @return  string
	 * @throws \UnexpectedValueException
	 * @throws \RuntimeException
	 */
	public function getToken($forceNew = false)
	{
		/** @var Session $session */
		$session = $this->container->get('session');

		$token = $session->get(static::TOKEN_KEY);

		// Create a token
		if ($token === null || $forceNew)
		{
			$token = $this->createToken(12);

			$session->set(static::TOKEN_KEY, $token);
		}

		return $token;
	}

	/**
	 * Get a token for specific user.
	 *
	 * @param   mixed   $userId   An identify of current user.
	 * @param   boolean $forceNew Force create new token.
	 *
	 * @return string
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 */
	public function getFormToken($userId = null, $forceNew = false)
	{
		if (User::hasHandler())
		{
			$userId = $userId ? : User::get()->id;
		}

		$userId = $userId ? : $this->container->get('session')->getId();

		$config = $this->container->get('config');

		return md5($config['system.secret'] . $userId . $this->getToken($forceNew));
	}

	/**
	 * checkToken
	 *
	 * @param  mixed  $userId
	 * @param  string $method
	 *
	 * @return  boolean
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 */
	public function checkToken($userId = null, $method = null)
	{
		/** @var Input $input */
		$input = $this->container->get('input');
		$token = $this->getFormToken($userId);

		if ($method)
		{
			$input = $input->$method;
		}

		if ($input->get($token, null, InputFilter::ALNUM) !== null)
		{
			return true;
		}

		return false;
	}

	/**
	 * Get the DI container.
	 *
	 * @return  Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Set the DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  mixed
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;

		return $this;
	}
}
