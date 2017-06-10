<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Security;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Crypt\Password;

/**
 * The Hasher class.
 *
 * @see Password
 *
 * @method  string    create(string $password)
 * @method  boolean   verify(string $password, string $hash)
 * @method  string    genRandomPassword(int $length = 8)
 * @method  string    getSalt()
 * @method  Password  setSalt(string $salt)
 * @method  int       getCost()
 * @method  Password  setCost(int $cost)
 * @method  int       getType()
 * @method  Password  setType(int $type)
 * @method  bool      isSodiumAlgo(int $type)
 * @method  bool      isSodiumHash(string $hash)
 *
 * @since  __DEPLOY_VERSION__
 */
class Hasher extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'hasher';
}
