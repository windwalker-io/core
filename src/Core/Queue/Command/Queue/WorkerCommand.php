<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Command\Queue;

use Windwalker\Console\Command\Command;

/**
 * The WorkerCommand class.
 *
 * @since  __DEPLOY_VERSION__
 */
class WorkerCommand extends Command
{
	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'worker';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Start a queue worker.';

	/**
	 * init
	 *
	 * @return  void
	 */
	protected function init()
	{
		parent::init();
	}

	protected function doExecute()
	{
		return true;
	}
}
