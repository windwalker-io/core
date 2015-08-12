<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Joomla\DateTime\DateTime;
use Windwalker\Core\DateTime\DateTimeHelper;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The DateTimeProvider class.
 * 
 * @since  2.0
 */
class DateTimeProvider implements ServiceProviderInterface
{


	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  void
	 */
	public function register(Container $container)
	{
		DateTimeHelper::setDefaultTimezone();

		$closure = function(Container $container)
		{
			$tz = $container->get('system.config')->get('system.timezone', 'UTC');

			return new DateTime('now', new \DateTimeZone($tz));
		};

		$container->set('datetime', $closure)
			->alias('date', 'datetime');
	}
}
