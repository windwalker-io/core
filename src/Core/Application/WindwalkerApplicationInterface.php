<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Application;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\EventInterface;

/**
 * Interface WindwalkerApplicationInterface
 *
 * @since  {DEPLOY_VERSION}
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
	public function getPackage($name);

	/**
	 * loadProviders
	 *
	 * @return  ServiceProviderInterface[]
	 */
	public function loadProviders();

	/**
	 * getPackages
	 *
	 * @return  AbstractPackage[]
	 */
	public function loadPackages();

	/**
	 * Trigger an event.
	 *
	 * @param   EventInterface|string $event The event object or name.
	 * @param   array                 $args  The arguments.
	 *
	 * @return  EventInterface  The event after being passed through all listeners.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function triggerEvent($event, $args = array());
}
