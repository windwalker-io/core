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
use Windwalker\Database\Driver\Mysql\MysqlDriver;
use Windwalker\DataMapper\DatabaseContainer;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Structure\Structure;

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
			/** @var Structure $config */
			$config = $container->get('config');

			$option = [
				'driver'   => $config->get('database.driver', 'mysql'),
				'host'     => $config->get('database.host', 'localhost'),
				'user'     => $config->get('database.user', 'root'),
				'password' => $config->get('database.password', ''),
				'database' => $config->get('database.name'),
				'prefix'   => $config->get('database.prefix', 'wind_'),
			];

			$db = DatabaseFactory::getDbo($option['driver'], $option);

			if ($db instanceof MysqlDriver && $config->get('database.mysql.strict', true))
			{
				$this->strictMode($db);
			}

			return $db;
		};

		$container->share(AbstractDatabaseDriver::class, $closure);

		DatabaseContainer::setDb(function () use ($container)
		{
		    return $container->get('database');
		});

		// For Exporter
		$closure = function(Container $container)
		{
			/** @var Structure $config */
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
	 * @param MysqlDriver $db
	 *
	 * @return  void
	 */
	public function strictMode(MysqlDriver $db)
	{
		// Set Mysql to strict mode
		$modes = array(
			// 'ONLY_FULL_GROUP_BY',
			'STRICT_TRANS_TABLES',
			'ERROR_FOR_DIVISION_BY_ZERO',
			'NO_AUTO_CREATE_USER',
			'NO_ENGINE_SUBSTITUTION',
			'NO_ZERO_DATE',
			'NO_ZERO_IN_DATE'
		);

		$db->connect()
			->getConnection()
			->exec("SET @@SESSION.sql_mode = '" . implode(',', $modes) . "';");
	}
}
