<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Package\Command\Package;

use Windwalker\Console\Command\Command;
use Windwalker\Console\Prompter\BooleanPrompter;
use Windwalker\Core\Console\ConsoleHelper;
use Windwalker\Core\Console\CoreCommandTrait;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Filesystem\File;

/**
 * The InstallCommand class.
 *
 * @since  {DEPLOY_VERSION}
 */
class InstallCommand extends Command
{
	use CoreCommandTrait;

	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'install';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Install package';

	/**
	 * Initialise command.
	 *
	 * @return void
	 *
	 * @since  2.0
	 */
	protected function init()
	{
		$this->addOption('hard')
			->description('Hard copy assets.')
			->defaultValue(false);
	}

	/**
	 * Execute this command.
	 *
	 * @return int
	 *
	 * @since  2.0
	 */
	protected function doExecute()
	{
		$env      = $this->getOption('env');
		$resolver = ConsoleHelper::getAllPackagesResolver($env, $this->console);
		$names    = $this->io->getArguments();

		if (!$names)
		{
			throw new \InvalidArgumentException('No package input.');
		}

		foreach ($names as $name)
		{
			$this->out()
				->out('Start installing package: <info>' . $name . '</info>')
				->out('---------------------------');

			$package = $resolver->getPackage($name);

			if (!$package)
			{
				$this->err('Package: ' . $name . ' not found.');
			}

			$this->installConfig($package);
			$this->syncAssets($package);
		}

		return true;
	}

	/**
	 * installConfig
	 *
	 * @param AbstractPackage $package
	 *
	 * @return  void
	 */
	protected function installConfig(AbstractPackage $package)
	{
		$dir = $package->getDir() . '/Resources/config';

		// Config
		$targetFolder = $this->console->get('path.etc') . '/package';
		$file = $dir . '/config.dist.php';
		$target = $targetFolder . '/' . $package->name . '.php';

		if (is_file($file) && with(new BooleanPrompter)->ask("File: <info>config.dist.php</info> exists,\n do you want to copy it to <info>etc/package/" . $package->name . '.php</info> [Y/n]: ', true))
		{
			if (is_file($target) && with(new BooleanPrompter)->ask('File exists, do you want to override it? [N/y]: ', false))
			{
				File::delete($target);
			}
			else
			{
				$this->out('  Config file: <comment>etc/package/' . $package->name . '.php</comment> exists, do not copy.');
			}

			if (!is_file($target) && File::copy($file, $target))
			{
				$this->out('  Copy to <info>etc/package/' . $package->name . '.php</info> successfully.');
			}
		}

		$file = $dir . '/secret.dist.yml';
		$target = $this->console->get('path.etc') . '/secret.yml';

		if (is_file($file) && with(new BooleanPrompter)->ask("File: <info>secret.dist.yml</info> exists,\n do you want to copy content to bottom of <info>etc/secret.yml</info> [Y/n]: ", true))
		{
			$secret = ltrim(file_get_contents($target));
			$new = file_get_contents($file);
			$secret = $secret . "\n# " . $package->name . "\n" . ltrim($new);

			File::write($target, $secret);

			$this->out('  Copy to <info>etc/secret.yml</info> successfully.');
		}
	}

	/**
	 * syncAssets
	 *
	 * @param AbstractPackage $package
	 *
	 * @return  void
	 */
	public function syncAssets(AbstractPackage $package)
	{
		try
		{
			$this->console->executeByPath('asset sync ' . $package->name, ['hard' => $this->getOption('hard')]);
		}
		catch (\Exception $e)
		{
			$this->err($e->getMessage());
		}
	}
}
