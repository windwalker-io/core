<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\SystemPackage\Command\System;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\Filesystem\File;
use Windwalker\Structure\Structure;

/**
 * The UpCommand class.
 *
 * @since  3.0
 */
class DownCommand extends CoreCommand
{
	/**
	 * Console(Argument) name.
	 *
	 * @var  string
	 */
	protected $name = 'down';

	/**
	 * The command description.
	 *
	 * @var  string
	 */
	protected $description = 'Make site offline.';

	/**
	 * The usage to tell user how to use this command.
	 *
	 * @var string
	 */
	protected $usage = '%s [options]';

	/**
	 * Property offline.
	 *
	 * @var  boolean
	 */
	protected $offline = true;

	/**
	 * Execute this command.
	 *
	 * @return int
	 *
	 * @since  2.0
	 */
	protected function doExecute()
	{
		$file = WINDWALKER_ETC . '/secret.yml';

		if (!is_file($file))
		{
			throw new \RuntimeException('File: etc/secret.yml not exists.');
		}

		$registry = (new Structure)->loadFile($file, 'yaml');
		$registry->set('system.offline', $this->offline);

		if (!File::write($file, $registry->toString('yaml', ['inline' => 4])))
		{
			throw new \RuntimeException('Writing etc/secret.yml file fail.');
		}

		$this->out()->out($this->description);

		return true;
	}
}
