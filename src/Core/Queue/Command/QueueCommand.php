<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Command;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Queue\Command\Queue;

/**
 * The QueueCommand class.
 *
 * @since  __DEPLOY_VERSION__
 */
class QueueCommand extends Command
{
	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'queue';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Queue management.';

	/**
	 * init
	 *
	 * @return  void
	 */
	protected function init()
	{
		$this->addCommand(Queue\WorkerCommand::class);
		$this->addCommand(Queue\TableCommand::class);
		$this->addCommand(Queue\FailTableCommand::class);
	}
}
