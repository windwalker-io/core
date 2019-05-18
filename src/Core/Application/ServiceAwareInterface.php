<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application;

/**
 * Interface ServiceAwareInterface
 *
 * @since  3.5.5
 */
interface ServiceAwareInterface
{
    /**
     * Create a new service.
     *
     * @param string $class
     * @param array  $args
     * @param bool   $protected
     *
     * @return  mixed
     *
     * @since  3.5
     */
    public function make(string $class, array $args = [], bool $protected = false);

    /**
     * Get service.
     *
     * @param string $class
     * @param bool   $forceNew
     *
     * @return  mixed
     *
     * @since  3.5
     */
    public function service(string $class, bool $forceNew = false);
}
