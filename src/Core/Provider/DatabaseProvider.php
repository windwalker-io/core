<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Database\Exporter\AbstractExporter;
use Windwalker\Database\DatabaseFactory;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\DataMapper\DatabaseContainer;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Registry\Registry;

/**
 * Class WhoopsProvider
 *
 * @since 1.0
 */
class DatabaseProvider implements ServiceProviderInterface
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
		$closure = function(Container $container)
		{
			/** @var Registry $config */
			$config = $container->get('config');

			$option = array(
				'driver'   => $config->get('database.driver', 'mysql'),
				'host'     => $config->get('database.host', 'localhost'),
				'user'     => $config->get('database.user', 'root'),
				'password' => $config->get('database.password', ''),
				'database' => $config->get('database.name'),
				'prefix'   => $config->get('database.prefix', 'wind_'),
			);

			return DatabaseFactory::getDbo($option['driver'], $option);
		};

		$container->share(AbstractDatabaseDriver::class, $closure);

		DatabaseContainer::setDb(function () use ($container)
		{
		    return $container->get('database');
		});

		// For Exporter
		$closure = function(Container $container)
		{
			/** @var Registry $config */
			$config = $container->get('config');

			$driver = $config->get('database.driver', 'mysql');

			$class = 'Windwalker\Core\Database\Exporter\\' . ucfirst($driver) . 'Exporter';

			return new $class;
		};

		$container->share(AbstractExporter::class, $closure);
	}

	/**
	 * strictMode
	 *
	 * @param Container $container
	 * @param boolean   $mode
	 */
	public static function strictMode(Container $container, $mode = null)
	{
		/** @var Registry $config */
		$config = $container->get('config');

		if ($config->get('database.driver') == 'mysql')
		{
			if ($mode)
			{
				$container->get('database')->setQuery("SET sql_mode = 'NO_ENGINE_SUBSTITUTION,STRICT_ALL_TABLES'")->execute();
			}
			else
			{
				$container->get('database')->setQuery("SET sql_mode = ''")->execute();
			}
		}
	}
}
