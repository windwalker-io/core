<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Provider;

use Windwalker\Database\DatabaseFactory;
use Windwalker\DataMapper\Adapter\AbstractDatabaseAdapter;
use Windwalker\DataMapper\Adapter\WindwalkerAdapter;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

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
			$config = $container->get('system.config');

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

		$container->share('system.database', $closure)
			->alias('database', 'system.database')
			->alias('db', 'system.database');

		// For DataMapper
		AbstractDatabaseAdapter::setInstance(
			function() use ($container)
			{
				return new WindwalkerAdapter($container->get('db'));
			}
		);

		// For Exporter
		$closure = function(Container $container)
		{
			$config = $container->get('system.config');

			$driver = $config->get('database.driver', 'mysql');

			$class = 'Windwalker\Core\Database\Exporter\\' . ucfirst($driver) . 'Exporter';

			return new $class;
		};

		$container->share('sql.exporter', $closure);
	}

	/**
	 * strictMode
	 *
	 * @param Container $container
	 *
	 * @return  void
	 */
	public static function strictMode(Container $container)
	{
		$config = $container->get('system.config');

		$mode = $config->get('database.mysql_strict_mode');

		if ($config->get('database.driver') == 'mysql' && $mode !== null)
		{
			if ($mode)
			{
				$container->get('system.database')->setQuery("SET sql_mode = 'NO_ENGINE_SUBSTITUTION,STRICT_ALL_TABLES'")->execute();
			}
			else
			{
				$container->get('system.database')->setQuery("SET sql_mode = ''")->execute();
			}
		}
	}
}
