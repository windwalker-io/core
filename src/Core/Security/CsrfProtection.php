<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Security;

use Windwalker\Core\Authenticate\User;
use Windwalker\Core\Ioc;
use Windwalker\Dom\HtmlElement;
use Windwalker\Filter\InputFilter;

/**
 * The CsrfProtection class.
 *
 * @since  2.0.9
 */
abstract class CsrfProtection
{
	const TOKEN_KEY = 'form.token';

	/**
	 * validate
	 *
	 * @return  boolean
	 */
	public static function validate()
	{
		if (!static::checkToken())
		{
			die('Invalid Token');
		}

		return true;
	}

	/**
	 * input
	 *
	 * @return  HtmlElement
	 */
	public static function input($attrs = array())
	{
		$attrs['name']  = static::getFormToken();
		$attrs['type']  = 'hidden';
		$attrs['value'] = 1;

		return new HtmlElement('input', null, $attrs);
	}

	/**
	 * createToken
	 *
	 * @param int $length
	 *
	 * @return  string
	 */
	public static function createToken($length = 12)
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
	 * getToken
	 *
	 * @param bool $forceNew
	 *
	 * @return  string
	 */
	public static function getToken($forceNew = false)
	{
		$session = Ioc::getSession();

		$token = $session->get(static::TOKEN_KEY);

		// Create a token
		if ($token === null || $forceNew)
		{
			$token = static::createToken(12);

			$session->set(static::TOKEN_KEY, $token);
		}

		return $token;
	}

	/**
	 * getFormToken
	 *
	 * @param bool $forceNew
	 *
	 * @return  string
	 */
	public static function getFormToken($forceNew = false)
	{
		$user = User::get();
		$config = Ioc::getConfig();

		return md5($config['system.secret'] . $user->id . static::getToken($forceNew));
	}

	/**
	 * checkToken
	 *
	 * @param string $method
	 *
	 * @return  boolean
	 */
	public static function checkToken($method = null)
	{
		$token = static::getFormToken();
		$input = Ioc::getInput();

		if ($method)
		{
			$input = $input->$method;
		}

		if ($input->get($token, '', InputFilter::ALNUM))
		{
			return true;
		}

		return false;
	}
}
