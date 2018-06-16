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
use Windwalker\Filesystem\File;
use Windwalker\Structure\Format\IniFormat;
use Windwalker\Structure\Structure;

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
    protected $usage = '%s <file> [<lang_code>] [<origin_lang_code>] [-p=package] [-r|--replace]';

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
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     * @since  3.2.8
     */
    protected function doExecute()
    {
        $file = $this->getArgument(0);
        $to   = $this->getArgument(1, $this->console->get('language.locale', 'en-GB'));
        $from = $this->getArgument(2, $this->console->get('language.default', 'en-GB'));

        if (!$file) {
            throw new WrongArgumentException('Please provide file name.');
        }

        $package     = null;
        $packageName = $this->getOption('p');

        if ($packageName) {
            $resolver = ConsoleHelper::getAllPackagesResolver();
            $package  = $resolver->getPackage($packageName);

            if (!$package) {
                throw new \RuntimeException('Package: ' . $packageName . ' not found.');
            }
        }

        $langPath = $package ? $package->getDir() . '/Resources/language' : WINDWALKER_RESOURCES . '/languages';

        $fromPath = $langPath . '/' . $from;
        $fromFile = $fromPath . '/' . $file;

        $toPath = $langPath . '/' . $to;
        $toFile = $toPath . '/' . $file;

        if (!is_file($fromFile)) {
            throw new \RuntimeException('File: ' . $fromFile . ' not exists.');
        }

        $structure = new Structure;

        $flat = $this->getOption('f');
        $sort = $this->getOption('s');

        $fromData = $structure->loadFile($fromFile, 'ini', ['processSections' => !$flat]);

        if (is_file($toFile)) {
            $structure->loadFile($toFile, 'ini', ['processSections' => !$flat, 'only_exists' => true]);
        }

        $data = $structure->toArray();

        if ($sort) {
            foreach ($data as $k => &$v) {
                if (\is_array($v)) {
                    ksort($v);
                }
            }

            unset($v);

            ksort($data);
        }

        $data = IniFormat::structToString($data);
        $replace = $this->getOption('r');

        $dest = $replace ? $toFile : WINDWALKER_TEMP . '/language/' . $to . '/' . $file;

        File::write($dest, rtrim($data) . "\n");

        $this->out()->out(sprintf('File created: <info>%s</info>', $dest));

        if (!$replace) {
            $this->out()->out('(You can use -r|--replace to just override language file instead save to tmp.)');
        }

        return true;
    }
}
