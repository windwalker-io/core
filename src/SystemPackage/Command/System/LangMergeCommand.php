<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\SystemPackage\Command\System;

use Windwalker\Console\Exception\WrongArgumentException;
use Windwalker\Core\Console\ConsoleHelper;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Filesystem\File;
use Windwalker\Structure\Format\IniFormat;
use Windwalker\Structure\Structure;
use Windwalker\Utilities\Arr;

/**
 * The ModeCommand class.
 *
 * @since  3.0
 */
class LangMergeCommand extends CoreCommand
{
	/**
	 * Console(Argument) name.
	 *
	 * @var  string
	 */
	protected $name = 'lang-merge';

	/**
	 * The command description.
	 *
	 * @var  string
	 */
	protected $description = 'Merge different language files and save to temp ro replace original file.';

	/**
	 * The usage to tell user how to use this command.
	 *
	 * @var string
	 */
	protected $usage = '%s <file> [<lang_code>] [<origin_lang_code>] [-p=package]';

	/**
	 * Initialise command.
	 *
	 * @return void
	 *
	 * @since  2.0
	 */
	protected function init()
	{
		$this->addOption('p')
			->alias('package')
			->description('Package name');

		$this->addOption('r')
			->alias('replace')
			->description('Replace current file instead save to tmp.')
			->defaultValue(false);

		$this->addOption('f')
			->alias('flat')
			->description('Flatten language keys as one level.')
			->defaultValue(false);

		$this->addOption('s')
			->alias('sort')
			->description('Sort language keys.')
			->defaultValue(false);
	}

	/**
	 * Execute this command.
	 *
	 * @return int
	 *
	 * @since  3.2.8
	 * @throws \RuntimeException
	 */
	protected function doExecute()
	{
		$file = $this->getArgument(0);
		$to   = $this->getArgument(1, $this->console->get('language.locale', 'en-GB'));
		$from = $this->getArgument(2, $this->console->get('language.default', 'en-GB'));

		if (!$file)
		{
			throw new WrongArgumentException('Please provide file name.');
		}

		$package = null;
		$name = $this->getOption('p');

		if ($name)
		{
			$resolver = ConsoleHelper::getAllPackagesResolver();
			$package = $resolver->getPackage($name);

			if (!$package)
			{
				throw new \RuntimeException('Package: ' . $name . ' not found.');
			}
		}

		$langPath = $package ? $package->getDir() . '/Resources/language' : WINDWALKER_RESOURCES . '/languages';

		$fromPath = $langPath . '/' . $from;
		$fromFile = $fromPath . '/' . $file;

		$toPath = $langPath . '/' . $to;
		$toFile = $toPath . '/' . $file;

		if (!is_file($fromFile))
		{
			throw new \RuntimeException('File: ' . $fromFile . ' not exists.');
		}

		$structure = new Structure;

		$flat = $this->getOption('f');
		$sort = $this->getOption('s');

		$fromData = $structure->loadFile($fromFile, 'ini', ['processSections' => !$flat]);

		if (is_file($toFile))
		{
			$structure->loadFile($toFile, 'ini', ['processSections' => !$flat]);
		}

		$data = $structure->toArray();

		if ($sort)
		{
			foreach ($data as $k => &$v)
			{
				if (is_array($v))
				{
					ksort($v);
				}
			}
		}

		$data = IniFormat::structToString($data);

		$dest = $this->getOption('r') ? $toFile : WINDWALKER_TEMP . '/language/' . $to . '/' . $file;

		File::write($dest, $data);

		$this->out(sprintf('File created: <info>%s</info>', $dest));

		return true;
	}
}
