<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Asset\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Attributes\AttributesAccessor;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\Input\InputOption;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Attributes\ViewModel;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\StrNormalize;

/**
 * The AssetSyncCommand class.
 *
 * @deprecated No use now.
 */
#[CommandWrapper(description: 'Asset sync helpers', hidden: true)]
class AssetSyncCommand implements CommandInterface
{
    /**
     * AssetSyncCommand constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected ApplicationInterface $app)
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
            'dir',
            InputArgument::OPTIONAL,
            'Where to find views.'
        );
        // $command->addArgument(
        //     'dest',
        //     InputArgument::OPTIONAL,
        //     'Where to copy files.'
        // );
        $command->addOption(
            'ns',
            null,
            InputOption::VALUE_REQUIRED,
            'The namespace start from this path.',
            $this->app->config('asset.namespace_base')
        );
        $command->addOption(
            'pretty',
            null,
            InputOption::VALUE_OPTIONAL,
            'JSON pretty print.',
            false
        );
        // $command->addOption(
        //     'type',
        //     null,
        //     InputOption::VALUE_REQUIRED,
        //     'css | js',
        //     'js'
        // );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     * @throws \JsonException
     */
    public function execute(IOInterface $io): int
    {
        $dir = $io->getArgument('dir') ?? '@source/Module';
        $dir = $this->app->path($dir);
        
        // $dest = $io->getArgument('dest');

        // if (!$dest) {
        //     throw new InvalidArgumentException('Please provide dest path.');
        // }

        // $this->dest = $this->app->path($dest);
        // $this->ns = $ns = (string) $io->getOption('ns');
        // $this->type = (string) $io->getOption('type');

        $map = [];

        AttributesAccessor::scanDirAndRunAttributes(
            ViewModel::class,
            $dir,
            (string) $io->getOption('ns'),
            handler: function (ViewModel $vm, string $className, FileObject $file) use ($io, &$map) {
                $this->handleAssets($vm, $className, $file, $io, $map);
            },
            options: \ReflectionAttribute::IS_INSTANCEOF
        );

        $flags = JSON_THROW_ON_ERROR;

        if ($io->getOption('pretty') !== false) {
            $flags |= JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        }

        $io->writeln(json_encode($map, $flags));

        return 0;
    }

    public function handleAssets(ViewModel $vm, string $className, FileObject $file, IOInterface $io, array &$map): void
    {
        $dir = $file->getPath();
        $ns = (string) $io->getOption('ns');

        $src = $dir . '/assets/**/*.{js,mjs}';
        $src = Path::relative($this->app->path('@root') . '/', $src);
        $dest = Path::clean(
                strtolower(ltrim($vm->getModuleName() ?? $this->guessName($className, $ns), '/\\'))
            ) . DIRECTORY_SEPARATOR;

        $map['js'][$src] = $dest;

        foreach ($vm->css as $cssFile) {
            $src = $dir . '/asset/' . $cssFile;
            $src = Path::relative($this->app->path('@root') . '/', $src);

            $map['css'][] = $src;
        }
    }

    protected function guessName(string $class, string $nsBase): string
    {
        $ref = new \ReflectionClass($class);
        $ns = $ref->getNamespaceName();

        return Str::removeLeft($ns, $nsBase);
    }
}
