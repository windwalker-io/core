<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Migration\Model;

use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Core\Migration\AbstractMigration;
use Windwalker\Core\Model\DatabaseModel;
use Windwalker\Data\Data;
use Windwalker\Data\DataSet;
use Windwalker\Database\Schema\Column\Timestamp;
use Windwalker\Database\Schema\Column\Varchar;
use Windwalker\Filesystem\File;
use Windwalker\Filesystem\Filesystem;
use Windwalker\String\StringNormalise;

/**
 * The MigrationsModel class.
 * 
 * @since  2.0
 */
class MigrationsModel extends DatabaseModel
{
	/**
	 * Property command.
	 *
	 * @var AbstractCommand
	 */
	protected $command;

	/**
	 * Property logTable.
	 *
	 * @var string
	 */
	protected $logTable = 'migration_log';

	/**
	 * getMigrations
	 *
	 * @return  array|DataSet
	 */
	public function getMigrations()
	{
		$path = $this['path'];

		$files = Filesystem::files($path);

		$migrations = new DataSet;

		foreach ($files as $file)
		{
			$ext = File::getExtension($file->getBasename());

			if ($ext != 'php')
			{
				continue;
			}

			$name = $file->getBasename();

			list($id, $name) = explode('_', $name, 2);

			$mig = new Data;

			$mig['id']      = $id;
			$mig['version'] = $id;
			$mig['name']    = File::stripExtension($name);
			$mig['class']   = StringNormalise::toCamelCase(File::stripExtension($name));
			$mig['file']    = $file->getBasename();
			$mig['path']    = $file->getPathname();

			$migrations[$id] = $mig;
		}

		return $migrations;
	}

	/**
	 * getVersions
	 *
	 * @return  array
	 */
	public function getVersions()
	{
		$this->initLogTable();

		$db = $this->db;

		return $this->fetch('versions', function() use ($db)
		{
			$query = $this->db->getQuery(true)
				->select('version')
				->from($this->logTable)
				->order('version ASC');

			return $this->db->setQuery($query)->loadColumn();
		});
	}

	/**
	 * getCurrentVersion
	 *
	 * @return  int
	 */
	public function getCurrentVersion()
	{
		$versions = $this->getVersions();

		if ($versions)
		{
			return end($versions);
		}

		return 0;
	}

	/**
	 * migrate
	 *
	 * @param string $version
	 *
	 * @return  void
	 */
	public function migrate($version = null)
	{
		$migrations = $this->getMigrations();
		$versions = $this->getVersions();
		$currentVersion = $this->getCurrentVersion();

		if (!count($migrations))
		{
			throw new \RuntimeException('No migrations found.');
		}

		if ($version === null)
		{
			$version = max(array_merge($versions, array_keys(iterator_to_array($migrations))));
		}
		else
		{
			if ($version != 0 && empty($migrations[$version]))
			{
				throw new \RuntimeException('Version is not valid.');
			}
		}

		$direction = ($version > $currentVersion) ? AbstractMigration::UP : AbstractMigration::DOWN;

		$migrations = iterator_to_array($migrations);

		if ($direction == AbstractMigration::DOWN)
		{
			krsort($migrations);

			foreach ($migrations as $migration)
			{
				if ($migration['version'] <= $version)
				{
					break;
				}

				if (in_array($migration['version'], $versions))
				{
					$this->executeMigration($migration, AbstractMigration::DOWN);
				}
			}
		}

		ksort($migrations);

		foreach ($migrations as $migration)
		{
			if ($migration['version'] > $version)
			{
				break;
			}

			if (!in_array($migration['version'], $versions))
			{
				$this->executeMigration($migration, AbstractMigration::UP);
			}
		}
	}

	/**
	 * executeMigration
	 *
	 * @param Data   $migrationItem
	 * @param string $direction
	 *
	 * @return  void
	 */
	public function executeMigration(Data $migrationItem, $direction = 'up')
	{
		$class = $migrationItem['class'];

		include_once $migrationItem['path'];

		$migration = new $class($this->getCommand(), $this->getDb());

		$start = time();

		$tran = $this->db->getTransaction()->start();

		try
		{
			$migration->$direction();
		}
		catch (\Exception $e)
		{
			$tran->rollback();

			throw new $e;
		}

		$tran->commit();

		$end = time();

		$this['log.' . $migrationItem['id']] = array(
			'id' => $migrationItem['id'],
			'direction' => $direction,
			'name' => $migrationItem['name']
		);

		$this->storeVersion($migrationItem, $direction, $start, $end);
	}

	/**
	 * storeVersion
	 *
	 * @param Data   $migration
	 * @param string $direction
	 * @param string $start
	 * @param string $end
	 *
	 * @return  void
	 */
	public function storeVersion($migration, $direction, $start, $end)
	{
		if ($direction == AbstractMigration::UP)
		{
			$data['version'] = $migration['version'];
			$data['start_time'] = gmdate('Y-m-d H:i:s', $start);
			$data['end_time'] = gmdate('Y-m-d H:i:s', $end);

			$this->db->getWriter()->insertOne($this->logTable, $data);
		}
		else
		{
			$query = $this->db->getQuery(true)
				->delete($this->logTable)
				->where('version = ' . $this->db->quote($migration['version']));

			$this->db->setQuery($query)->execute();
		}
	}

	/**
	 * initLogTable
	 *
	 * @param string $name
	 *
	 * @return  void
	 */
	public function initLogTable($name = null)
	{
		$name = $name ? : $this->logTable;

		$table = $this->db->getTable($name);

		if ($table->exists())
		{
			return;
		}

		$table->addColumn(new Varchar('version'))
			->addColumn(new Timestamp('start_time'))
			->addColumn(new Timestamp('end_time'))
			->create();
	}

	/**
	 * Method to get property Command
	 *
	 * @return  mixed
	 */
	public function getCommand()
	{
		return $this->command;
	}

	/**
	 * Method to set property command
	 *
	 * @param   mixed $command
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setCommand(AbstractCommand $command)
	{
		$this->command = $command;

		return $this;
	}
}
