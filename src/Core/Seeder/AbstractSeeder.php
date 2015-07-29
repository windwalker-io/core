<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Seeder;

use Windwalker\Console\Command\Command;
use Windwalker\Database\Driver\DatabaseDriver;
use Windwalker\Utilities\Reflection\ReflectionHelper;

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
	 * @var DatabaseDriver
	 */
	protected $db;

	/**
	 * Property io.
	 *
	 * @var Command
	 */
	protected $command;

	/**
	 * Class init.
	 *
	 * @param DatabaseDriver $db
	 * @param Command        $command
	 */
	public function __construct(DatabaseDriver $db = null, Command $command = null)
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

		$this->command->out('Import seeder ' . get_class($seeder));

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
	 * clean
	 *
	 * @param AbstractSeeder|string $seeder
	 *
	 * @return  static
	 */
	public function clean($seeder = null)
	{
		if (is_string($seeder))
		{
			$ref = new \ReflectionClass($this);

			include_once dirname($ref->getFileName()) . '/' . $seeder . '.php';

			$seeder = new $seeder;
		}

		$seeder->setDb($this->db);
		$seeder->setCommand($this->command);

		$this->command->out('Clean seeder ' . get_class($seeder));

		$seeder->doClean();

		return $this;
	}

	/**
	 * doClean
	 *
	 * @return  void
	 */
	public function doClean()
	{
		// Override it.
	}

	/**
	 * Method to get property Db
	 *
	 * @return  DatabaseDriver
	 */
	public function getDb()
	{
		return $this->db;
	}

	/**
	 * Method to set property db
	 *
	 * @param   DatabaseDriver $db
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setDb(DatabaseDriver $db)
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
}
