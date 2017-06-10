<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Command\Queue;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Console\CoreCommandTrait;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Filesystem\File;

/**
 * The WorkerCommand class.
 *
 * @since  3.2
 */
class RestartCommand extends Command
{
	use CoreCommandTrait;

	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'restart';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Send restart signal to all workers.';

	/**
	 * init
	 *
	 * @return  void
	 */
	protected function init()
	{
		$this->addOption('t')
			->alias('time')
			->defaultValue('now')
			->description('The time to restart all workers.');
	}

	/**
	 * doExecute
	 *
	 * @return  bool
	 */
	protected function doExecute()
	{
		$file = $this->console->get('path.temp') . '/queue/restart';

		File::write($file, Chronos::create($this->getOption('time'))->toUnix());

		$this->out('Sent restart signal to all workers.');

		return true;
	}
}
