<?php

/**
 * Part of starter project.
 *
 * @copyright    Copyright (C) 2021 __ORGANIZATION__.
 * @license        LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\Assert\TypeAssert;
use Windwalker\Utilities\StrInflector;
use Windwalker\Utilities\StrNormalize;

/**
 * The GenRevertCommand class.
 */
#[CommandWrapper(
    description: 'Revise file to template.'
)]
class GenReviseCommand implements CommandInterface
{
    /**
     * GenReviseCommand constructor.
     */
    public function __construct(protected ConsoleApplication $app)
    {
    }

    /**
     * configure
     *
     * @param    Command  $command
     *
     * @return    void
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'file',
            InputArgument::REQUIRED,
            'File path or with wildcards.',
        );

        $command->addArgument(
            'dest',
            InputArgument::REQUIRED,
            'Thr dest dir.',
        );

        $command->addOption(
            'name',
            null,
            InputOption::VALUE_REQUIRED,
            'Pascal name of this file.',
        );

        // $command->addOption(
        //     'class',
        //     null,
        //     InputOption::VALUE_REQUIRED,
        //     'Pascal class name of this file.',
        // );
        
        $command->addOption(
            'ns',
            null,
            InputOption::VALUE_REQUIRED,
            'The namespace base.',
            'App\\Module'
        );
    }

    /**
     * Executes the current command.
     *
     * @param    IOInterface  $io
     *
     * @return    int Return 0 is success, 1-255 is failure.
     */
    public function execute(IOInterface $io): int
    {
        $path = $io->getArgument('file');
        $dest = $io->getArgument('dest');

        $path = $this->app->path($path);

        if (str_contains($path, '*')) {
            $files = Filesystem::glob($path);
        } else {
            $files = [new FileObject($path, dirname($path))];
        }

        $data = $this->getReplaceData($io);

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $relativePath = $file->getRelativePathname();

            $relativePath = strtr($relativePath, $data);

            $content = file_get_contents($file->getPathname());
            $content = strtr($content, $data);

            $destPath = $dest . '/' . $relativePath . '.tpl';

            Filesystem::write($destPath, $content);

            $io->writeln('[<info>WRITE</info>] ' . $destPath);
        }
        
        return 0;
    }

    protected function getNamesapce(IOInterface $io): string
    {
        [$dest] = $this->getNameParts($io);
        $ns  = $io->getOption('ns');

        $ns .= '\\' . $dest;

        return StrNormalize::toClassNamespace($ns);
    }

    // protected function getDestPath(IOInterface $io): string
    // {
    //     [$dest] = $this->getNameParts($io);
    //     $dir  = $io->getOption('dir');
    //
    //     return Path::normalize($this->app->path($dir . '/' . $dest));
    // }

    protected function getNameParts(IOInterface $io): array
    {
        $name = $io->getArgument('name');
        $names = preg_split('/\/|\\\\/', $name);
        $name = array_pop($names);
        $dest = implode('/', $names);

        return [$dest, $name];
    }

    protected function getReplaceData(IOInterface $io): array
    {
        $ns = $io->getOption('ns');
        $name = $io->getOption('name');
        // $className = $io->getOption('class');

        TypeAssert::assert((bool) $ns, 'Please provide --ns {Namespace}');
        TypeAssert::assert((bool) $name, 'Please provide --name {Name}');
        // TypeAssert::assert((bool) $className, 'Please provide --class {Class}');

        return [
            StrNormalize::toClassNamespace($ns) => '{% $ns %}',
            StrInflector::toPlural($name) => '{% plural($name) %}',
            $name => '{% $name %}',
            strtolower($name) => '{% strtolower($name) %}',
            strtoupper($name) => '{% strtoupper($name) %}',
            StrNormalize::toPascalCase($name) => '{% pascal($name) %}',
            StrNormalize::toCamelCase($name) => '{% camel($name) %}',
            StrNormalize::toKebabCase($name) => '{% kebab($name) %}',
            StrNormalize::toSnakeCase($name) => '{% snake($name) %}',
            StrNormalize::toDotSeparated($name) => '{% dot($name) %}',
            // $className => '{% $className %}',
            '<?php' => '{% $phpOpen %}',
            '?>' => '{% $phpClose %}',
        ];
    }
}
