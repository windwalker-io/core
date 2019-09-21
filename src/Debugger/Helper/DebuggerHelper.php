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
use function Windwalker\h;
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
            $value = h('pre', [], Arr::dump($value, $depth));
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

    /**
     * getAllowIps
     *
     * @return  array
     *
     * @since  3.5.12.1
     */
    public static function getAllowIps(): array
    {
        $config = Ioc::getConfig()->extract('dev');

        $allowIps = array_merge(
            ['127.0.0.1', 'fe80::1', '::1'],
            array_map('trim', explode(',', (string) $config['allow_ips']))
        );

        return $allowIps;
    }

    /**
     * getCurrentIp
     *
     * @see https://www.opencli.com/php/php-get-real-ip
     *
     * @return  string
     *
     * @TODO: Use ServerHelper::getCurrentIp() if available.
     *
     * @since  3.5.12.1
     */
    public static function getCurrentIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return (string) $ip;
    }

    /**
     * ipIsAllow
     *
     * @param string|null $ip
     *
     * @return  bool
     *
     * @since  3.5.12.1
     */
    public static function ipIsAllow(?string $ip = null): bool
    {
        $ip = $ip ?? static::getCurrentIp();
        $allowIps = static::getAllowIps();

        return in_array($ip, $allowIps, true);
    }
}
