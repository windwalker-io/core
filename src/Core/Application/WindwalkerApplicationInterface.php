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
use Windwalker\DI\ServiceProviderInterface;
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
	 * loadProviders
	 *
	 * @return  ServiceProviderInterface[]
	 */
	public static function loadProviders();

	/**
	 * getPackages
	 *
	 * @return  AbstractPackage[]
	 */
	public static function loadPackages();

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
	public function triggerEvent($event, $args = array());

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
	 * addMessage
	 *
	 * @param string|array $messages
	 * @param string       $type
	 *
	 * @return  static
	 */
	public function addMessage($messages, $type = Bootstrap::MSG_INFO);
}
