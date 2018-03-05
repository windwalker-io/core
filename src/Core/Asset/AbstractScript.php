<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Asset;

use Windwalker\Core\Package\PackageHelper;
use Windwalker\Utilities\Arr;

/**
 * The ScriptManager class.
 *
 * @see    ScriptManager
 *
 * @since  3.0
 */
abstract class AbstractScript
{
    /**
     * Property asset.
     *
     * @var  callable|ScriptManager
     */
    public static $instance;

    /**
     * Property packageClass.
     *
     * @var  string
     */
    protected static $packageClass;

    /**
     * inited
     *
     * @param   string $name
     * @param   mixed  ...$data
     *
     * @return bool
     */
    protected static function inited($name, ...$data)
    {
        return static::getInstance()->inited($name, ...$data);
    }

    /**
     * getInitedId
     *
     * @param   mixed ...$data
     *
     * @return  string
     */
    protected static function getInitedId(...$data)
    {
        return static::getInstance()->getInitedId(...$data);
    }

    /**
     * getAsset
     *
     * @return  AssetManager
     */
    protected static function getAsset()
    {
        return static::getInstance()->getAsset();
    }

    /**
     * packageName
     *
     * @param null $class
     *
     * @return  string|\Windwalker\Core\Package\AbstractPackage
     */
    protected static function packageName($class = null)
    {
        $class = $class ?: static::$packageClass;

        return PackageHelper::getAlias($class);
    }

    /**
     * addStyle
     *
     * @param string $url
     * @param array  $options
     * @param array  $attribs
     *
     * @return  AssetManager
     */
    protected static function addCSS($url, $options = [], $attribs = [])
    {
        return static::getAsset()->addCSS($url, $options, $attribs);
    }

    /**
     * addScript
     *
     * @param string $url
     * @param array  $options
     * @param array  $attribs
     *
     * @return  AssetManager
     */
    protected static function addJS($url, $options = [], $attribs = [])
    {
        return static::getAsset()->addJS($url, $options, $attribs);
    }

    /**
     * import
     *
     * @param string $url
     * @param array  $options
     * @param array  $attribs
     *
     * @return  AssetManager
     */
    protected static function import($url, array $options = [], array $attribs = [])
    {
        return static::getAsset()->import($url, $options, $attribs);
    }

    /**
     * internalStyle
     *
     * @param string $content
     *
     * @return  AssetManager
     */
    protected static function internalCSS($content)
    {
        return static::getAsset()->internalCSS($content);
    }

    /**
     * internalStyle
     *
     * @param string $content
     *
     * @return  AssetManager
     */
    protected static function internalJS($content)
    {
        return static::getAsset()->internalJS($content);
    }

    /**
     * getJSObject
     *
     * @param mixed ...$data The data to merge.
     * @param bool  $quote   Quote object key or not, default is false.
     *
     * @return  string
     */
    public static function getJSObject(...$data)
    {
        $quote = array_pop($data);

        if (!is_bool($quote)) {
            $data[] = $quote;
            $quote  = false;
        }

        if (count($data) > 1) {
            $result = [];

            foreach ($data as $array) {
                $result = static::mergeOptions($result, $array);
            }
        } else {
            $result = $data[0];
        }

        return static::getAsset()->getJSObject($result, $quote);
    }

    /**
     * mergeOptions
     *
     * @param array $options1
     * @param array $options2
     * @param bool  $recursive
     *
     * @return  array
     */
    public static function mergeOptions($options1, $options2, $recursive = true)
    {
        if (!$recursive) {
            return array_merge($options1, $options2);
        }

        return Arr::mergeRecursive($options1, $options2);
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param   string $method The method name.
     * @param   array  $args   The arguments of method call.
     *
     * @return  mixed
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::getInstance();

        return $instance->$method(...$args);
    }

    /**
     * getInstance
     *
     * @return  ScriptManager
     */
    protected static function getInstance()
    {
        if (is_callable(static::$instance)) {
            $callable = static::$instance;

            static::$instance = $callable();
        }

        if (!static::$instance instanceof ScriptManager) {
            throw new \LogicException('Instance of ScriptManager should be ' . ScriptManager::class);
        }

        return static::$instance;
    }
}
