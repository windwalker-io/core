<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\SystemPackage\Command;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Console\ConsoleHelper;
use Windwalker\Core\Console\CoreCommandTrait;

/**
 * The DeployCommand class.
 *
 * @since  {DEPLOY_VERSION}
 */
class RunCommand extends Command
{
	use CoreCommandTrait;

	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'run';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Run custom scripts.';

	/**
	 * init
	 *
	 * @return  void
	 */
	protected function init()
	{
	}

	/**
	 * doExecute
	 *
	 * @return  boolean
	 */
	protected function doExecute()
	{
		$profiles = $this->io->getArguments();

		if (!$profiles)
		{
			throw new \InvalidArgumentException('Please enter at least one profile name.');
		}

		$resolver = ConsoleHelper::getAllPackagesResolver();

		$scripts = (array) $this->console->get('console.script');

		foreach ((array) ConsoleHelper::loadPackages() as $name => $package)
		{
			$scripts = array_merge($scripts, (array) $resolver->getPackage($name)->get('console.script'));
		}

		foreach ($profiles as $profile)
		{
			if (isset($scripts[$profile]))
			{
				$this->executeScriptProfile($scripts[$profile]);
			}
		}

		$this->out()->out('Complete script running.');

		return true;
	}

	/**
	 * executeScriptProfile
	 *
	 * @param   array  $scripts
	 *
	 * @return  void
	 */
	protected function executeScriptProfile($scripts)
	{
		foreach ($scripts as $script)
		{
			if ($this->executeScript($script) !== 0)
			{
				throw new \RuntimeException('Running script fail...');
			}
		}
	}

	/**
	 * executeScript
	 *
	 * @param   string  $script
	 *
	 * @return  integer
	 */
	protected function executeScript($script)
	{
		$this->console->addMessage('>>> ' . $script);

		$descriptorspec = array(
			0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
			1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
			2 => array("pipe", "w")    // stderr is a pipe that the child will write to
		);

		flush();
		$process = proc_open($script, $descriptorspec, $pipes, realpath('./'), array());

		if (is_resource($process))
		{
			while ($s = fgets($pipes[1])) {
				print $s;
				flush();
			}
		}

		return $code;
	}
}
