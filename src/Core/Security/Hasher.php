<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2017 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Security;

use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The Hasher class.
 *
 * @see    ModernPassword
 *
 * @method static string    create(string $password, int $algo = 1, array $options = []))
 * @method static boolean   verify(string $password, string $hash)
 * @method static boolean   needsRehash(string $password, int $algo = 1, array $options = [])
 * @method static string    genRandomPassword(int $length = 8)
 * @method static string    getSalt()
 * @method static ModernPassword  setSalt(string $salt)
 * @method static int       getCost()
 * @method static ModernPassword  setCost(int $cost)
 * @method static int       getType()
 * @method static ModernPassword  setType(int $type)
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
     * phpcs:disable
    */
    protected static $_key = 'hasher';
    // phpcs:enable
}
