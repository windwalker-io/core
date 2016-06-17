<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Asset\Command\Asset;

use Windwalker\Console\Command\Command;
use Windwalker\Filesystem\Folder;

/**
 * The SyncCommand class.
 * 
 * @since  2.1.1
 */
class MakesumCommand extends Command
{
	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'makesum';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Make media sum files';

	/**
	 * initialise
	 *
	 * @return  void
	 */
	public function init()
	{
	}

	/**
	 * doExecute
	 *
	 * @return  int
	 */
	protected function doExecute()
	{
		Folder::create(WINDWALKER_CACHE . '/riki');

		$sum = md5(uniqid());

		file_put_contents(WINDWALKER_CACHE . '/riki/MD5SUM', $sum);

		$this->out('Create SUM: <info>' . $sum . '</info> at <info>' . WINDWALKER_CACHE . '/riki/MD5SUM</info>');

		return true;
	}
}
