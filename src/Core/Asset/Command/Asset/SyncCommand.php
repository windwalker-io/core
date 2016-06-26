<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Asset\Command\Asset;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Console\ConsoleHelper;
use Windwalker\Environment\PlatformHelper;
use Windwalker\Filesystem\Folder;
use Windwalker\Core\Utilities\Symlink;

/**
 * The SyncCommand class.
 * 
 * @since  2.1.1
 */
class SyncCommand extends Command
{
	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'sync';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Sync asset to main media folder';

	/**
	 * initialise
	 *
	 * @return  void
	 */
	public function init()
	{
		$this->addOption('s')
			->alias('symbol')
			->defaultValue(true)
			->description('Use symbol link to link asset folders');

		$this->addOption('hard')
			->defaultValue(false)
			->description('Hard copy assets to media folders');
	}

	/**
	 * doExecute
	 *
	 * @return  int
	 */
	protected function doExecute()
	{
		$hard = $this->getOption('hard');

		// Prepare path
		$name = $this->io->getArgument(0);

		/** @var WebApplication $env */
		$env = $this->getOption('env');

		$resolver = ConsoleHelper::getAllPackagesResolver($env, $this->console);

		if (!$name)
		{
			throw new \InvalidArgumentException('No package input.');
		}

		$package = $resolver->getPackage($name);

		if ($package)
		{
			$dir = $package->getDir() . '/Resources/asset';
		}
		else
		{
			throw new \InvalidArgumentException('Package ' . $name . ' not found.');
		}

		if (!is_dir($dir))
		{
			throw new \InvalidArgumentException('This package has no <comment>/Resources/asset</comment> folder so nothing synced.');
		}

		$target = $this->getArgument(1, $name);
		$target = $this->console->get('path.public') . '/asset/' . $target;

		$symlink = new Symlink;

		if (is_link($target))
		{
			throw new \RuntimeException('Link ' . $target . ' already created.');
		}

		if ($hard)
		{
			$this->hardCopy($dir, $target);

			$this->out('Copy folder ' . $dir . ' to ' . $target);
		}
		else
		{
			$this->out($symlink->make($dir, $target));

			if (!PlatformHelper::isWindows())
			{
				$this->out('Link success ' . $dir . ' <====> ' . $target);
			}
		}

		return true;
	}

	/**
	 * hardCopy
	 *
	 * @param string $src
	 * @param string $dest
	 *
	 * @return  void
	 */
	protected function hardCopy($src, $dest)
	{
		Folder::copy($src, $dest);
	}
}
