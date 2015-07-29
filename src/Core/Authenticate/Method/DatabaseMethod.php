<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Authenticate\Method;

use Windwalker\Authenticate\Authenticate;
use Windwalker\Authenticate\Credential;
use Windwalker\Authenticate\Method\AbstractMethod;
use Windwalker\Crypt\Password;
use Windwalker\DataMapper\DataMapper;

/**
 * The DatabaseMethod class.
 * 
 * @since  2.0
 */
class DatabaseMethod extends AbstractMethod
{
	/**
	 * authenticate
	 *
	 * @param Credential $credential
	 *
	 * @return  integer
	 */
	public function authenticate(Credential $credential)
	{
		if (!$credential->username || !$credential->password)
		{
			$this->status = Authenticate::EMPTY_CREDENTIAL;

			return false;
		}

		$datamapper = new DataMapper('users');

		$user = $datamapper->findOne(array('username' => $credential->username));

		if ($user->isNull())
		{
			$this->status = Authenticate::USER_NOT_FOUND;

			return false;
		}

		$password = new Password;

		if (!$password->verify($credential->password, $user->password))
		{
			$this->status = Authenticate::INVALID_CREDENTIAL;

			return false;
		}

		$credential->bind($user);

		$this->status = Authenticate::SUCCESS;

		return true;
	}
}
