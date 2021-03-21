<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
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
use Windwalker\Utilities\StrNormalise;

/**
 * The AssetSyncCommand class.
 */
#[CommandWrapper(description: 'Asset sync helpers', hidden: true)]
class AssetSyncCommand implements CommandInterface
{
    protected string $ns = '';

    protected string $dest = '';

    protected array $map = [];

    protected string $type = '';

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
            'App\\Component'
        );
        $command->addOption(
            'type',
            null,
            InputOption::VALUE_REQUIRED,
            'css | js',
            'js'
        );
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
        $dir = $io->getArgument('dir') ?? '@source/Component';
        $dir = $this->app->path($dir);
        // $dest = $io->getArgument('dest');

        // if (!$dest) {
        //     throw new InvalidArgumentException('Please provide dest path.');
        // }

        // $this->dest = $this->app->path($dest);
        $this->ns = $ns = (string) $io->getOption('ns');
        $this->type = (string) $io->getOption('type');

        AttributesAccessor::scanDirAndRunAttributes(
            ViewModel::class,
            $dir,
            $ns,
            handler: [$this, 'handleAssets'],
            options: \ReflectionAttribute::IS_INSTANCEOF
        );

        $io->writeln(json_encode($this->map, JSON_THROW_ON_ERROR));

        $this->map = [];

        return 0;
    }

    public function handleAssets(ViewModel $vm, string $className, FileObject $file): void
    {
        $dir = $file->getPath();

        if ($this->type === 'js') {
            $src = $dir . '/asset/**/*.{js,mjs}';
            $src = Path::makeRelativeFrom($src, $this->app->path('@root') . '/');
            $dest = Path::clean(
                strtolower(ltrim($vm->getName() ?? $this->guessName($className), '/\\'))
            ) . '/';
            // $dest = Path::makeRelativeFrom(
            //     Path::clean($this->dest . '/' . $dest) . '/',
            //     $this->app->path('@root') . '/'
            // ) . '/';

            $this->map[$src] = $dest;
        }

        if ($this->type === 'css') {
            foreach ($vm->css as $cssFile) {
                $src = $dir . '/asset/' . $cssFile;
                $src = Path::makeRelativeFrom($src, $this->app->path('@root') . '/');

                $this->map[] = $src;
            }
        }

        // foreach ($vm->js as $jsFile) {
        //     $src = $dir . '/view/' . $jsFile;
        //     $dest = strtolower($vm->getName() ?? $this->guessName($className)) . '/' . $jsFile;
        //
        //     $dest = ltrim(Path::clean($dest), '/\\');
        //
        //     $this->map[$src] = $dest;
        // }
        //
        // foreach ($vm->modules as $jsFile) {
        //     $src = $dir . '/view/' . $jsFile;
        //     $dest = strtolower($vm->getName() ?? $this->guessName($className)) . '/' . $jsFile;
        //
        //     $dest = ltrim(Path::clean($dest), '/\\');
        //
        //     $this->map[$src] = $dest;
        // }
    }

    protected function guessName(string $class): string
    {
        $ref = new \ReflectionClass($class);
        $ns = $ref->getNamespaceName();

        return Str::removeLeft($ns, $this->ns);
    }
}
