<?php
/**
 * Part of moon project.
 *
 * @copyright  Copyright (C) 2018 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

// TODO: Remove this if Edge fix last() issue.
if (!function_exists('last')) {
    /**
     * last
     *
     * @param array $array
     *
     * @return  mixed
     *
     * @since  __DEPLOY_VERSION__
     */
    function last($array)
    {
        return end($array);
    }
}
