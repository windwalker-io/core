<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Migration\Command\Migration;

use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Core\Migration\Model\MigrationsModel;
use Windwalker\Filesystem\File;
use Windwalker\String\StringHelper;

/**
 * The CreateCommand class.
 * 
 * @since  2.0
 */
class CreateCommand extends AbstractCommand
{
	/**
	 * An enabled flag.
	 *
	 * @var bool
	 */
	public static $isEnabled = true;

	/**
	 * Console(Argument) name.
	 *
	 * @var  string
	 */
	protected $name = 'create';

	/**
	 * The command description.
	 *
	 * @var  string
	 */
	protected $description = 'Create a migration version.';

	/**
	 * The usage to tell user how to use this command.
	 *
	 * @var string
	 */
	protected $usage = 'create <cmd><command></cmd> <option>[option]</option>';

	/**
	 * Configure command information.
	 *
	 * @return void
	 */
	public function init()
	{
	}

	/**
	 * Execute this command.
	 *
	 * @return int|void
	 */
	protected function doExecute()
	{
		$migration = new MigrationsModel;

		$migration['path'] = $this->console->get('migration.dir');

		$migrations = $migration->getMigrations();

		$name = $this->getArgument(0);

		if (!$name)
		{
			throw new \InvalidArgumentException('Missing first argument "name"');
		}

		// Check name not exists
		foreach ($migrations as $migItem)
		{
			if (strtolower($name) == strtolower($migItem['name']))
			{
				throw new \RuntimeException('Migration: <info>' . $name . "</info> has exists. \nFile at: <info>" . $migItem['path'] . '</info>');
			}
		}

		$date = gmdate('YmdHis');

		$file = $date . '_' . ucfirst($name) . '.php';

		// Get template
		$tmpl = file_get_contents(__DIR__ . '/../../../Resources/Templates/migration/migration.php.dist');

		$tmpl = StringHelper::parseVariable($tmpl, array('version' => $date, 'className' => ucfirst($name)));

		// Get file path
		$filePath = $this->console->get('migration.dir') . '/' . $file;

		if (is_file($filePath))
		{
			throw new \RuntimeException(sprintf('File already exists: %s', $filePath));
		}

		// Write it
		File::write($filePath, $tmpl);

		$this->out()->out('Migration version: <info>' . $file . '</info> created.');
		$this->out('File path: <info>' . realpath($filePath). '</info>');

		return true;
	}
}
