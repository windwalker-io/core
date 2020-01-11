<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2018 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Security;

use Windwalker\Crypt\CryptHelper;
use Windwalker\Crypt\HasherInterface;

/**
 * The ModernPassword class.
 *
 * @since  3.4.6
 */
class ModernPassword implements HasherInterface
{
    /**
     * create
     *
     * @param string $text
     *
     * @param int    $algo
     * @param array  $options
     *
     * @return  string
     */
    public function create($text, $algo = PASSWORD_DEFAULT, array $options = [])
    {
        return password_hash($text, $algo, $options);
    }

    /**
     * Verify the password.
     *
     * @param   string $text The plain text.
     * @param   string $hash The hashed text.
     *
     * @return  boolean  Verify success or not.
     */
    public function verify($text, $hash)
    {
        return password_verify($text, $hash);
    }

    /**
     * needsRehash
     *
     * @param string $text
     * @param int    $algo
     * @param array  $options
     *
     * @return  bool
     *
     * @since  3.4.6
     */
    public function needsRehash($text, $algo = PASSWORD_DEFAULT, array $options = [])
    {
        return password_needs_rehash($text, $algo, $options);
    }

    /**
     * Generate a random password.
     *
     * This is a fork of Joomla JUserHelper::genRandomPassword()
     *
     * @param   integer $length Length of the password to generate
     *
     * @return  string  Random Password
     *
     * @see     https://github.com/joomla/joomla-cms/blob/staging/libraries/joomla/user/helper.php#L642
     * @since   2.0.9
     * @throws \Exception
     */
    public static function genRandomPassword($length = 8)
    {
        $salt = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $base = strlen($salt);
        $password = '';

        /*
         * Start with a cryptographic strength random string, then convert it to
         * a string with the numeric base of the salt.
         * Shift the base conversion on each character so the character
         * distribution is even, and randomize the start shift so it's not
         * predictable.
         */
        $random = random_bytes($length + 1);
        $shift = ord($random[0]);

        for ($i = 1; $i <= $length; ++$i) {
            $password .= $salt[($shift + ord($random[$i])) % $base];

            $shift += ord($random[$i]);
        }

        return $password;
    }
}
