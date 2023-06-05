<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\SubCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\Input\InputOption;
use Windwalker\Console\InteractInterface;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Generator\CodeGenerator;
use Windwalker\DI\Attributes\Inject;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\StrNormalize;

/**
 * The AbstractGeneratorSubCommand class.
 */
abstract class AbstractGeneratorSubCommand implements CommandInterface, InteractInterface
{
    #[Inject]
    protected CodeGenerator $codeGenerator;

    #[Inject]
    protected ConsoleApplication $app;

    protected string $defaultNamespace = 'App\\Module';

    protected string $defaultDir = 'src/Module';

    protected bool $requireDest = true;

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
            'name',
            InputArgument::OPTIONAL,
        );

        // $command->addArgument(
        //     'dest',
        //     InputArgument::OPTIONAL,
        // );

        $command->addOption(
            'dir',
            'd',
            InputOption::VALUE_REQUIRED,
            'Root dir.',
            $this->defaultDir
        );

        $command->addOption(
            'ns',
            null,
            InputOption::VALUE_REQUIRED,
            'Namespace.',
            $this->defaultNamespace
        );

        $command->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force override files'
        );
    }

    /**
     * Interaction with user.
     *
     * @param  IOInterface  $io
     *
     * @return  void
     */
    public function interact(IOInterface $io): void
    {
        $dir = $io->getOption('dir');

        if (!$io->getArgument('name')) {
            $io->setArgument('name', $io->ask('Controller name (camel case): '));
        }

        // if (!$io->getArgument('dest') && $this->requireDest) {
        //     $io->setArgument('dest', $io->ask("Dest path (<comment>from: $dir/</comment>): "));
        // }
    }

    protected function getNamesapce(IOInterface $io, ?string $suffix = null): string
    {
        [$dest] = $this->getNameParts($io, $suffix);
        $ns = $io->getOption('ns');

        $ns .= '\\' . $dest;

        return StrNormalize::toClassNamespace($ns);
    }

    protected function getDestPath(IOInterface $io, ?string $suffix = null): string
    {
        [$dest] = $this->getNameParts($io, $suffix);
        $dir = $io->getOption('dir');

        return Path::normalize($this->app->path($dir . '/' . $dest));
    }

    protected function getNameParts(IOInterface $io, ?string $suffix = null): array
    {
        $name = $io->getArgument('name');
        $names = preg_split('/\/|\\\\/', $name);
        $name = $names[array_key_last($names)];

        if (($suffix && str_ends_with($name, $suffix)) || !$suffix) {
            array_pop($names);
        }

        $dest = implode('/', $names);

        return [$dest, $name];
    }

    protected function getViewPath(string $suffix = ''): string
    {
        $path = $this->getBaseDir() . '/views/code';

        if ($suffix) {
            $path .= '/' . $suffix;
        }

        return Path::normalize($path);
    }

    protected function getBaseDir(): string
    {
        return __DIR__ . '/../../../..';
    }

    protected static function pathPop(string $path): string
    {
        $paths = explode('/', $path);

        array_pop($paths);

        return implode('/', $paths);
    }
}
