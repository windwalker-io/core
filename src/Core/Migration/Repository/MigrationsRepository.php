<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Migration\Repository;

use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Core\Migration\AbstractMigration;
use Windwalker\Core\Model\ModelRepository;
use Windwalker\Core\Model\Traits\CliOutputModelTrait;
use Windwalker\Core\Model\Traits\DatabaseModelTrait;
use Windwalker\Data\Data;
use Windwalker\Data\DataSet;
use Windwalker\Database\Schema\Schema;
use Windwalker\Filesystem\File;
use Windwalker\Filesystem\Filesystem;
use Windwalker\String\SimpleTemplate;
use Windwalker\String\StringNormalise;

/**
 * The MigrationsModel class.
 * 
 * @since  2.0
 */
class MigrationsRepository extends ModelRepository
{
	use DatabaseModelTrait;
	use CliOutputModelTrait;

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

			if ($ext !== 'php')
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

		$query = $this->db->getQuery(true)
			->select('version')
			->from($this->logTable)
			->order('version ASC');

		return $this->db->setQuery($query)->loadColumn();
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

		$count = 0;

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

				$count++;
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

			$count++;
		}

		if (!$count)
		{
			$this->out('No change.');
		}
	}

	/**
	 * executeMigration
	 *
	 * @param Data   $migrationItem
	 * @param string $direction
	 *
	 * @throws  \Exception
	 *
	 * @return  void
	 */
	public function executeMigration(Data $migrationItem, $direction = 'up')
	{
		$class = $migrationItem['class'];

		include_once $migrationItem['path'];

		$migration = new $class($this->getCommand(), $this->getDb());

		$start = time();

		// Note: Mysql dose not support transaction of DDL, but PostgreSQL, Oracle, SQLServer and SQLite does.
		$tran = $this->db->getTransaction()->start();

		try
		{
			$tmpl = <<<LOG

    Migrate <cmd>%s</cmd> the version: <info>%s_%s</info>
LOG;

			$this->out(sprintf(
				$tmpl,
				strtoupper($direction),
				$migrationItem['id'],
				$migrationItem['name']
			));

			$migration->$direction();

			$tmpl = <<<LOG
    ------------------------------------------------------------
    <option>Success</option>
LOG;

			$this->out($tmpl);
		}
		catch (\Exception $e)
		{
			$tran->rollback();

			throw $e;
		}

		$tran->commit();

		$end = time();

		$this['log.' . $migrationItem['id']] = [
			'id' => $migrationItem['id'],
			'direction' => $direction,
			'name' => $migrationItem['name']
		];

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

		$table->create(function (Schema $schema)
		{
			$schema->varchar('version');
			$schema->timestamp('start_time');
			$schema->timestamp('end_time');
		});
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

	/**
	 * copyMigration
	 *
	 * @param string $name
	 * @param string $template
	 *
	 * @return  void
	 */
	public function copyMigration($name, $template)
	{
		$migrations = $this->getMigrations();

		// Check name not exists
		foreach ($migrations as $migration)
		{
			if (strtolower($name) == strtolower($migration['name']))
			{
				throw new \RuntimeException('Migration: <info>' . $name . "</info> has exists. \nFile at: <info>" . $migration['path'] . '</info>');
			}
		}

		$date = gmdate('YmdHis');

		$file = $date . '_' . ucfirst($name) . '.php';

		$tmpl = file_get_contents($template);

		$tmpl = SimpleTemplate::render($tmpl, ['version' => $date, 'className' => ucfirst($name)]);

		// Get file path
		$filePath = $this->get('path') . '/' . $file;

		if (is_file($filePath))
		{
			throw new \RuntimeException(sprintf('File already exists: %s', $filePath));
		}

		// Write it
		File::write($filePath, $tmpl);

		$this->out()->out('Migration version: <info>' . $file . '</info> created.');
		$this->out('File path: <info>' . realpath($filePath). '</info>');
	}
}
