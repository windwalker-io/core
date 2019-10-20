<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2017 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Mailer;

require_once __DIR__ . '/idna/idna_convert.class.php';

/**
 * The Punycode class.
 *
 * @since  3.2.2
 */
class Punycode
{
    /**
     * Property instances.
     *
     * @var  \idna_convert
     */
    protected static $instance;

    /**
     * getInstance
     *
     * @return \idna_convert
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new \idna_convert();
        }

        return static::$instance;
    }

    /**
     * Transforms a UTF-8 string to a Punycode string
     *
     * @param   string $utfString The UTF-8 string to transform
     *
     * @return  string  The punycode string
     */
    public static function encode($utfString)
    {
        return static::getInstance()->encode($utfString);
    }

    /**
     * Transforms a Punycode string to a UTF-8 string
     *
     * @param   string $punycodeString The Punycode string to transform
     *
     * @return  string  The UF-8 URL
     */
    public static function decode($punycodeString)
    {
        return static::getInstance()->decode($punycodeString);
    }

    /**
     * toASCII
     *
     * @param string $string
     *
     * @return  string
     */
    public static function toAscii($string)
    {
        $string = explode('@', $string);

        // Not addressing UTF-8 user names
        $new = $string[0];

        if (!empty($string[1])) {
            $domainExploded = explode('.', $string[1]);
            $newdomain      = '';

            foreach ($domainExploded as $domainex) {
                $domainex  = static::encode($domainex);
                $newdomain .= $domainex . '.';
            }

            $newdomain = substr($newdomain, 0, -1);
            $new       = $new . '@' . $newdomain;
        }

        return $new;
    }

    /**
     * toUtf8
     *
     * @param string $string
     *
     * @return  string
     */
    public static function toUtf8($string)
    {
        $string = explode('@', $string);

        // Not addressing UTF-8 user names
        $new = $string[0];

        if (!empty($string[1])) {
            $domainExploded = explode('.', $string[1]);
            $newdomain      = '';

            foreach ($domainExploded as $domainex) {
                $domainex  = static::decode($domainex);
                $newdomain .= $domainex . '.';
            }

            $newdomain = substr($newdomain, 0, -1);
            $new       = $new . '@' . $newdomain;
        }

        return $new;
    }
}
