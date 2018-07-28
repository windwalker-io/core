<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Debugger\Helper;

use Windwalker\Core\Facade\AbstractFacade;
use Windwalker\Debugger\DebuggerPackage;
use Windwalker\Dom\HtmlElement;
use Windwalker\Ioc;
use Windwalker\Profiler\Point\Collector;
use Windwalker\Utilities\Arr;

/**
 * The DebuggerHelper class.
 *
 * @method  static Collector  getInstance()
 *
 * @since  2.1.1
 */
abstract class DebuggerHelper extends AbstractFacade
{
    /**
     * Property _key.
     *
     * @var  string
     * phpcs:disable
    */
    protected static $_key = 'debugger.collector';
    // phpcs:enable

    /**
     * addData
     *
     * @param   string $key
     * @param   mixed  $value
     * @param   int    $depth
     */
    public static function addCustomData($key, $value, $depth = 5)
    {
        try {
            $collector = static::getInstance();
        } catch (\UnexpectedValueException $e) {
            return;
        }

        $data = $collector['custom.data'];

        if ($data === null) {
            $data = [];
        }

        if (is_array($value) || is_object($value)) {
            $value = new HtmlElement('pre', Arr::dump($value, $depth));
        }

        if (isset($data[$key]) && is_string($data[$key])) {
            $data[$key]   = [$data[$key]];
            $data[$key][] = (string) $value;
        } else {
            $data[$key] = (string) $value;
        }

        $collector['custom.data'] = $data;
    }

    /**
     * addMessage
     *
     * @param string|array $message
     * @param string       $type
     */
    public static function addMessage($message, $type = 'default')
    {
        if (is_array($message)) {
            foreach ($message as $msg) {
                static::addMessage($msg);
            }

            return;
        }

        try {
            $collector = static::getInstance();
        } catch (\UnexpectedValueException $e) {
            return;
        }

        $data = $collector['debug.messages'];

        if ($data === null) {
            $data = [];
        }

        $data[] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => microtime(true),
        ];

        $collector['debug.messages'] = $data;
    }

    /**
     * getQueries
     *
     * @return  array
     */
    public static function getQueries()
    {
        $collector = static::getInstance();

        return $collector['database.queries'];
    }

    /**
     * enableConsole
     *
     * @return  void
     */
    public static function enableConsole()
    {
        if (!Ioc::exists('windwalker.debugger')) {
            return;
        }

        /** @var DebuggerPackage $package */
        $package = Ioc::get('windwalker.debugger');

        $package->enableConsole();
    }

    /**
     * disableConsole
     *
     * @return  void
     */
    public static function disableConsole()
    {
        if (!Ioc::exists('windwalker.debugger')) {
            return;
        }

        /** @var DebuggerPackage $package */
        $package = Ioc::get('windwalker.debugger');

        $package->disableConsole();
    }
}
