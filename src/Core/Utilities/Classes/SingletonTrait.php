<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT Taiwan, Inc. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Utilities\Classes;

/**
 * The MultiSingletonTrait class.
 *
 * @since  3.0
 */
trait SingletonTrait
{
    /**
     * Property instances.
     *
     * @var  array
     */
    protected static $instances = [];

    /**
     * getInstance
     *
     * @param array ...$args
     *
     * @return static
     */
    public static function getInstance(...$args)
    {
        $class = get_called_class();

        if (empty(static::$instances[$class])) {
            static::$instances[$class] = new $class(...$args);
        }

        return static::$instances[$class];
    }

    /**
     * setInstance
     *
     * @param object $instance
     *
     * @return  mixed
     */
    public static function setInstance($instance)
    {
        $class = get_called_class();

        return static::$instances[$class] = $instance;
    }
}
