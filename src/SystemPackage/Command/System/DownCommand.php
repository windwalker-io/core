<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\SystemPackage\Command\System;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\Filesystem\File;

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
		$tmpFile = WINDWALKER_TEMP . '/offline';

		if ($this->offline)
		{
			File::write($tmpFile, 'off');
		}
		elseif (is_file($tmpFile))
		{
			File::delete($tmpFile);
		}

		$this->out()->out($this->description);

		return true;
	}
}
