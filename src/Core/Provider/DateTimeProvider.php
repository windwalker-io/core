<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\DateTime\DateTime;
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
	 * Property tz.
	 *
	 * @var  string
	 */
	protected $tz;

	/**
	 * DateTimeProvider constructor.
	 *
	 * @param  string  $tz
	 */
	public function __construct($tz = 'UTC')
	{
		$this->tz = $tz ? : 'UTC';
	}

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  void
	 */
	public function register(Container $container)
	{
		$tz = $this->tz;

		DateTime::setDefaultTimezone($tz);

		$closure = function(Container $container) use ($tz)
		{
			$tz = $container->get('system.config')->get('system.timezone', $tz);

			return new DateTime('now', new \DateTimeZone($tz));
		};

		$container->set('datetime', $closure)
			->alias('date', 'datetime');
	}
}
