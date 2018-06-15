<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2018 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Language\Translator;

if (!function_exists('__')) {
    /**
     * Translate
     *
     * @param string $text
     * @param array  ...$args
     *
     * @return  string
     *
     * @since  3.3
     */
    function __($text, ...$args)
    {
        if ($args === []) {
            return Translator::translate($text);
        }

        return Translator::sprintf($text, ...$args);
    }
}

if (!function_exists('trans')) {
    /**
     * trans
     *
     * @param string $text
     * @param array  ...$args
     *
     * @return  string
     *
     * @since  3.3
     */
    function trans($text, ...$args)
    {
        if ($args === []) {
            return Translator::translate($text);
        }

        return Translator::sprintf($text, ...$args);
    }
}

if (!function_exists('__plural')) {
    /**
     * __plural
     *
     * @param string $text
     * @param int    $number
     * @param array  ...$args
     *
     * @return  string
     *
     * @since  3.3
     */
    function __plural($text, $number = 1, ...$args)
    {
        return Translator::plural($text, $number, ...$args);
    }
}

if (!function_exists('date_compare')) {
    /**
     * date_compare
     *
     * @param string|\DateTimeInterface $date1
     * @param string|\DateTimeInterface $date2
     * @param string                    $operator
     *
     * @return  bool|int
     *
     * @since  3.3
     */
    function date_compare($date1, $date2, $operator = null)
    {
        return Chronos::compare($date1, $date2, $operator);
    }
}

if (!function_exists('html_escape')) {
    /**
     * html_escape
     *
     * @param string $string
     * @param bool   $nl2br
     * @param bool   $doubleEncode
     *
     * @return  string
     *
     * @since  3.4
     */
    function html_escape($string, $nl2br = false, $doubleEncode = true)
    {
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8', $doubleEncode);

        if ($nl2br) {
            $string = nl2br($string);
        }

        return $string;
    }
}
