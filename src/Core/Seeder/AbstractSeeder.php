<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Seeder;

use Windwalker\Console\Command\Command;
use Windwalker\Database\Command\AbstractTable;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\Environment\PlatformHelper;

/**
 * The AbstractSeeder class.
 *
 * @since  2.0
 */
abstract class AbstractSeeder
{
	/**
	 * Property db.
	 *
	 * @var AbstractDatabaseDriver
	 */
	protected $db;

	/**
	 * Property io.
	 *
	 * @var Command
	 */
	protected $command;

	/**
	 * Property count.
	 *
	 * @var  int
	 */
	protected $count = 0;

	/**
	 * Class init.
	 *
	 * @param AbstractDatabaseDriver $db
	 * @param Command                $command
	 */
	public function __construct(AbstractDatabaseDriver $db = null, Command $command = null)
	{
		$this->db = $db;
		$this->command = $command;
	}

	/**
	 * execute
	 *
	 * @param AbstractSeeder|string $seeder
	 *
	 * @return  static
	 */
	public function execute($seeder = null)
	{
		if (is_string($seeder))
		{
			$ref = new \ReflectionClass($this);

			include_once dirname($ref->getFileName()) . '/' . $seeder . '.php';

			$seeder = new $seeder;
		}

		$seeder->setDb($this->db)
			->setCommand($this->command);

		$this->command->out()->out('Import seeder <info>' . get_class($seeder) . '</info>');

		$seeder->doExecute();

		return $this;
	}

	/**
	 * doExecute
	 *
	 * @return  void
	 */
	abstract public function doExecute();

	/**
	 * clear
	 *
	 * @param AbstractSeeder|string $seeder
	 *
	 * @return  static
	 */
	public function clear($seeder = null)
	{
		if (is_string($seeder))
		{
			$ref = new \ReflectionClass($this);

			include_once dirname($ref->getFileName()) . '/' . $seeder . '.php';

			$seeder = new $seeder;
		}

		$seeder->setDb($this->db);
		$seeder->setCommand($this->command);

		$this->command->out('Clear seeder <comment>' . get_class($seeder) . '</comment>');

		$seeder->doClear();

		return $this;
	}
	/**
	 * doClear
	 *
	 * @return  void
	 */
	public function doClear()
	{
	}

	/**
	 * Get DB table.
	 *
	 * @param $name
	 *
	 * @return  AbstractTable
	 */
	public function getTable($name)
	{
		return $this->db->getTable($name, true);
	}

	/**
	 * truncate
	 *
	 * @param $name
	 *
	 * @return  static
	 */
	public function truncate($name)
	{
		$this->getTable($name)->truncate();

		return $this;
	}

	/**
	 * Method to get property Db
	 *
	 * @return  AbstractDatabaseDriver
	 */
	public function getDb()
	{
		return $this->db;
	}

	/**
	 * Method to set property db
	 *
	 * @param   AbstractDatabaseDriver $db
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setDb(AbstractDatabaseDriver $db)
	{
		$this->db = $db;

		return $this;
	}

	/**
	 * Method to get property Command
	 *
	 * @return  Command
	 */
	public function getCommand()
	{
		return $this->command;
	}

	/**
	 * Method to set property command
	 *
	 * @param   Command $command
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setCommand(Command $command)
	{
		$this->command = $command;

		return $this;
	}

	/**
	 * outCounting
	 *
	 * @return  Command
	 */
	public function outCounting()
	{
		// @see  https://gist.github.com/asika32764/19956edcc5e893b2cbe3768e91590cf1
		if (PlatformHelper::isWindows())
		{
			$loading = ['|', '/', '-', '\\'];
		}
		else
		{
			$loading = ['◐','◓','◑','◒'];
		}

		$this->count++;

		$icon = $loading[$this->count % count($loading)];

		$this->command->out("\r  ({$this->count}) $icon", false);

		return $this->command;
	}
}
