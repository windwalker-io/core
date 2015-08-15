<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Migration;

use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Database\Driver\AbstractDatabaseDriver;

/**
 * The AbstractMigration class.
 * 
 * @since  2.0
 */
abstract class AbstractMigration
{
	const UP = 'up';
	const DOWN = 'down';

	/**
	 * Property db.
	 *
	 * @var  AbstractDatabaseDriver
	 */
	protected $db;

	/**
	 * Property command.
	 *
	 * @var  AbstractCommand
	 */
	protected $command;

	/**
	 * Property version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Class init.
	 *
	 * @param AbstractCommand         $command
	 * @param AbstractDatabaseDriver  $db
	 */
	public function __construct(AbstractCommand $command, AbstractDatabaseDriver $db)
	{
		$this->command = $command;

		$this->db = $db;
	}

	/**
	 * up
	 *
	 * @return  void
	 */
	abstract public function up();

	/**
	 * down
	 *
	 * @return  void
	 */
	abstract public function down();

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
	public function setDb($db)
	{
		$this->db = $db;

		return $this;
	}

	/**
	 * Method to get property Command
	 *
	 * @return  AbstractCommand
	 */
	public function getCommand()
	{
		return $this->command;
	}

	/**
	 * Method to set property command
	 *
	 * @param   AbstractCommand $command
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setCommand($command)
	{
		$this->command = $command;

		return $this;
	}

	/**
	 * Method to get property Version
	 *
	 * @return  string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * Method to set property version
	 *
	 * @param   string $version
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setVersion($version)
	{
		$this->version = $version;

		return $this;
	}
}
