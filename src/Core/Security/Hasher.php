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
 * @see    Password
 *
 * @method static string    create(string $password)
 * @method static boolean   verify(string $password, string $hash)
 * @method static string    genRandomPassword(int $length = 8)
 * @method static string    getSalt()
 * @method static Password  setSalt(string $salt)
 * @method static int       getCost()
 * @method static Password  setCost(int $cost)
 * @method static int       getType()
 * @method static Password  setType(int $type)
 * @method static bool      isSodiumAlgo(int $type)
 * @method static bool      isSodiumHash(string $hash)
 *
 * @since  3.2
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
