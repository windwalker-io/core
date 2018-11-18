<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2018 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Security;

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
}
