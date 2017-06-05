<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Command\Queue;

use Windwalker\Console\Command\Command;
use Windwalker\Console\Exception\WrongArgumentException;
use Windwalker\Core\Console\CoreCommandTrait;
use Windwalker\Utilities\Arr;

/**
 * The WorkerCommand class.
 *
 * @since  3.2
 */
class RemoveFailedCommand extends Command
{
	use CoreCommandTrait;

	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'remove-failed';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Remove failed jobs.';

	/**
	 * Property usage.
	 *
	 * @var  string
	 */
	protected $usage = '%s <cmd><ids...></cmd> <option>[option]</option>';

	/**
	 * init
	 *
	 * @return  void
	 */
	protected function init()
	{
		$this->addOption('a')
			->alias('all')
			->defaultValue(false)
			->description('Clear all failed jobs.');
	}

	/**
	 * doExecute
	 *
	 * @return  bool
	 */
	protected function doExecute()
	{
		$failer = $this->console->container->get('queue.failer');

		$all = $this->getOption('all');

		if ($all)
		{
			$ids = array_column($failer->all(), 'id');
		}
		else
		{
			$ids = $this->io->getArguments();

			if (!count($ids))
			{
				throw new WrongArgumentException('No id provided');
			}
		}

		foreach ($ids as $id)
		{
			$failer->remove($id);

			if (!$all)
			{
				$this->out(sprintf('Remove failed-job: <info>%s</info>', $id));

				$failer->remove($id);
			}
		}

		if ($all)
		{
			$failer->clear();

			$this->out('All failed jobs cleared.');
		}

		return true;
	}
}
