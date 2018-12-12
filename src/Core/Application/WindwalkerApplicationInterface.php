<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Application;

use Windwalker\Core\Frontend\Bootstrap;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\DI\Container;
use Windwalker\Event\EventInterface;

/**
 * Interface WindwalkerApplicationInterface
 *
 * @since  2.0
 */
interface WindwalkerApplicationInterface
{
    /**
     * getPackage
     *
     * @param string $name
     *
     * @return  AbstractPackage
     */
    public function getPackage($name = null);

    /**
     * addPackage
     *
     * @param string          $name
     * @param AbstractPackage $package
     *
     * @return  static
     */
    public function addPackage($name, AbstractPackage $package);

    /**
     * Trigger an event.
     *
     * @param   EventInterface|string $event The event object or name.
     * @param   array                 $args  The arguments.
     *
     * @return  EventInterface  The event after being passed through all listeners.
     *
     * @since   2.0
     */
    public function triggerEvent($event, $args = []);

    /**
     * Method to get property Container
     *
     * @return  Container
     */
    public function getContainer();

    /**
     * getName
     *
     * @return  string
     */
    public function getName();

    /**
     * Method to get property Mode
     *
     * @return  string
     */
    public function getMode();

    /**
     * addMessage
     *
     * @param string|array $messages
     * @param string       $type
     *
     * @return  static
     */
    public function addMessage($messages, $type = Bootstrap::MSG_INFO);

    /**
     * isConsole
     *
     * @return  boolean
     */
    public function isConsole();

    /**
     * isWeb
     *
     * @return  boolean
     */
    public function isWeb();

    /**
     * isOffline
     *
     * @return  bool
     */
    public function isOffline();

    /**
     * make
     *
     * @param string $class
     * @param array  $args
     * @param bool   $protected
     *
     * @return  mixed
     *
     * @since  __DEPLOY_VERSION__
     */
    public function make($class, array $args = [], $protected = false);
}
