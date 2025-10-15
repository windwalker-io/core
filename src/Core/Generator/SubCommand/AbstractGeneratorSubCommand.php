<?php

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
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageRegistry;
use Windwalker\DI\Attributes\Inject;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\Str;
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

    protected string $baseNamespace = 'App\\';

    protected string $baseDir = 'src/';

    protected string $defaultNamespace = 'Module';

    protected string $defaultDir = 'Module';

    protected bool $requireDest = true;

    protected AbstractPackage|false|null $destPackage = null;

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
        );

        $command->addOption(
            'ns',
            null,
            InputOption::VALUE_REQUIRED,
            'Namespace.',
        );

        $command->addOption(
            'pkg',
            'p',
            InputOption::VALUE_REQUIRED,
            'The package name to auto detect dir and namespace.'
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
        if (!$io->getArgument('name')) {
            $io->setArgument('name', $io->ask('Name (camel case): '));
        }

        // if (!$io->getArgument('dest') && $this->requireDest) {
        //     $io->setArgument('dest', $io->ask("Dest path (<comment>from: $dir/</comment>): "));
        // }
    }

    protected function getNamespace(IOInterface $io, ?string $suffix = null): string
    {
        $this->resolvePackage($io);

        [$dest] = $this->getNameParts($io, $suffix);
        $ns = Str::ensureRight($io->getOption('ns') ?: $this->getDefaultNamespace(), '\\');

        $ns .= $dest;

        return StrNormalize::toClassNamespace($ns);
    }

    protected function getCustomNamespace(IOInterface $io, string $nsSuffix, ?string $suffix = null): string
    {
        $this->resolvePackage($io);

        [$dest] = $this->getNameParts($io, $suffix);
        $ns = Str::ensureRight($io->getOption('ns') ?: $this->getDefaultNamespace(), '\\');

        $ns .= $nsSuffix . '\\' . $dest;

        return StrNormalize::toClassNamespace($ns);
    }

    protected function getDestPath(IOInterface $io, ?string $suffix = null): string
    {
        $this->resolvePackage($io);

        [$dest] = $this->getNameParts($io, $suffix);

        $dir = $io->getOption('dir') ?: $this->getDefaultDir();

        return Path::normalize($this->app->path($dir . '/' . $dest));
    }

    protected function getCustomDestPath(IOInterface $io, string $subFolder, ?string $suffix = null): string
    {
        $this->resolvePackage($io);

        [$dest] = $this->getNameParts($io, $suffix);

        $dest = $subFolder . '/' . $dest;

        $dir = $io->getOption('dir') ?: $this->getDefaultDir();

        return Path::normalize($this->app->path($dir . '/' . $dest));
    }

    protected function getRawDestPath(IOInterface $io, string $subFolder = '', ?string $suffix = null): string
    {
        [$dest] = $this->getNameParts($io, $suffix);

        return trim(
            Str::ensureRight($subFolder, '/') . $dest,
            '/'
        );
    }

    protected function getRootDir(IOInterface $io): string
    {
        $this->resolvePackage($io);

        $dir = $io->getOption('dir');

        if ($dir) {
            return $dir;
        }

        if ($this->destPackage) {
            return $this->destPackage::root();
        }

        return $this->app->path('@root');
    }

    /**
     * @param  IOInterface  $io
     * @param  string|null  $suffix
     *
     * @return  array{ string, string, string }  dest, name, stage
     */
    protected function getNameParts(IOInterface $io, ?string $suffix = null): array
    {
        $name = $io->getArgument('name');

        return static::splitNameParts($name, $suffix);
    }

    /**
     * @param  string       $name
     * @param  string|null  $suffix
     *
     * @return  array{ string, string, string }  dest, name, stage
     */
    public static function splitNameParts(string $name, ?string $suffix = null): array
    {
        $names = preg_split('/\/|\\\\/', $name);
        $name = $names[array_key_last($names)];
        $stage = $names[array_key_first($names)];

        if (($suffix && str_ends_with($name, $suffix)) || !$suffix) {
            array_pop($names);
        }

        $dest = implode('/', $names);

        return [$dest, $name, $stage];
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

    protected function resolvePackage(IOInterface $io): ?AbstractPackage
    {
        if ($this->destPackage) {
            return $this->destPackage;
        }

        $pkg = $io->getOption('pkg');

        if (!$pkg) {
            $this->destPackage = false;

            return null;
        }

        $package = $this->app->retrieve(PackageRegistry::class)->getPackage($pkg);

        if ($package) {
            $this->destPackage = $package;

            $this->baseNamespace = $package::namespace() . '\\';
            $this->baseDir = $package::dir() . DIRECTORY_SEPARATOR;

            return $this->destPackage;
        }

        $this->destPackage = false;

        return null;
    }

    public function getDefaultNamespace(): string
    {
        return Str::ensureRight($this->baseNamespace . $this->defaultNamespace, '\\');
    }

    public function getDefaultDir(): string
    {
        return Str::ensureRight($this->baseDir, '/') . $this->defaultDir;
    }
}
