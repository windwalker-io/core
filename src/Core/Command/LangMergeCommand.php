<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\Input\InputOption;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Folder;
use Windwalker\Filesystem\Path;

use function Windwalker\fs;

/**
 * The LangMergeCommand class.
 */
#[CommandWrapper(
    description: 'Merge language files.'
)]
class LangMergeCommand implements CommandInterface
{
    public function __construct(protected ConsoleApplication $app)
    {
    }

    /**
     * configure
     *
     * @param  Command  $command
     *
     * @return  void
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'filename',
            InputArgument::REQUIRED,
            'File name to merge.'
        );

        $command->addArgument(
            'target_locale',
            InputArgument::OPTIONAL,
            'The target file locale.',
            $this->app->config('language.locale') ?? 'en-US'
        );

        $command->addArgument(
            'from_locale',
            InputArgument::OPTIONAL,
            'The locale to copy from.',
            $this->app->config('language.fallback') ?? 'en-US'
        );

        $command->addOption(
            'dir',
            'd',
            InputOption::VALUE_REQUIRED,
            'Base language dir.'
        );

        $command->addOption(
            'replace',
            'r',
            InputOption::VALUE_NONE,
            'Replace current file instead save to tmp.'
        );

        $command->addOption(
            'sort',
            's',
            InputOption::VALUE_NONE,
            'Sort language keys.'
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     */
    public function execute(IOInterface $io): int
    {
        $filename = $io->getArgument('filename');
        $to = $io->getArgument('target_locale');
        $from = $io->getArgument('from_locale');

        $dir = $io->getOption('dir') ?? $this->app->path('@languages');
        $sort = $io->getOption('sort');
        $replace = $io->getOption('replace');

        $dir = Path::realpath($dir);

        if (str_contains($filename, '*')) {
            $files = Filesystem::glob($dir . '/' . $from . '/**/*');
        } else {
            $files = [new FileObject($dir . '/' . $from . '/' . $filename)];
        }

        foreach ($files as $file) {
            $fromFile = $file;
            $toFile = fs($dir . '/' . $to . '/' . $file->getBasename());

            if (!$fromFile?->exists()) {
                throw new RuntimeException('File: ' . $fromFile . ' not exists.');
            }

            $data = $fromFile->readAndParse()->collapse();

            if ($toFile->exists()) {
                $targetData = $toFile->readAndParse()
                    ->collapse()
                    ->only($data->keys()->dump());

                $data = $data->load($targetData);
            }

            if ($sort) {
                $data = $data->sortKeys();
            }

            $dest = $replace ? $toFile : fs(WINDWALKER_TEMP . '/language/' . $to . '/' . $filename);

            $dest->write($data->toString($toFile->getExtension()));

            $io->writeln(sprintf('File handled: <info>%s</info>', $dest));
        }

        if (!$replace) {
            $io->newLine();
            $io->writeln('(You can use -r|--replace to just override language file instead save to tmp.)');
        }

        return 0;
    }
}
