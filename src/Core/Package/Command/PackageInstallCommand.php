<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Package\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\InstallResource;
use Windwalker\Core\Package\PackageInstaller;
use Windwalker\Core\Package\PackageRegistry;

use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;

use function Windwalker\collect;

/**
 * The PackageInstallCommand class.
 */
#[CommandWrapper(description: 'Install package resources.')]
class PackageInstallCommand implements CommandInterface
{
    /**
     * @var IOInterface
     */
    private ?IOInterface $io = null;

    /**
     * PackageInstallCommand constructor.
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
            'packages',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
        );
        $command->addOption(
            'tag',
            't',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Tags to install.'
        );
        $command->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_OPTIONAL,
            'Do not copy any files.',
            false
        );
        $command->addOption(
            'force',
            'f',
            InputOption::VALUE_OPTIONAL,
            'Force override files.',
            false
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
        $this->io = $io;

        $packages = (array) $io->getArgument('packages');
        $tags = (array) $io->getOption('tag');

        $registry = $this->app->make(PackageRegistry::class);

        // Discover and prepare-install
        $installer = $registry->prepareInstall();

        if (!$packages && !$tags) {
            $targets = $this->askForTargets($registry);
        } else {
            $targets = [];

            if (!$packages) {
                foreach ($registry->getPackages() as $package) {
                    $name = $package::getName();
                    $tagNames = array_keys($installer->getChild($name)->tags);

                    if (array_intersect($tagNames, $tags)) {
                        $packages[] = $package::getName();
                    }
                }
            }

            foreach ($packages as $package) {
                $targets[$package] ??= [];

                foreach ($tags as $tag) {
                    $targets[$package][] = $tag;
                }
            }
        }

        $this->validateTargets($registry, $packages, $tags);

        // Install
        foreach ($targets as $package => $tags) {
            $pkgInstaller = $installer->getChild($package);

            $io->writeln("Installing: <comment>$package</comment>");

            if ($tags === []) {
                $this->install($pkgInstaller->installResources->dump());
            } else {
                foreach ($tags as $tag) {
                    $this->install($pkgInstaller->tags[$tag]->dump());
                }
            }
        }

        return 0;
    }

    protected function install(array $resources): void
    {
        $root = $this->app->path('@root');
        $dry = $this->io->getOption('dry-run') !== false;
        $force = $this->io->getOption('force') !== false;

        foreach ($resources as $files) {
            if ($files !== []) {
                foreach ($files as $src => $dest) {
                    $prefix = '[<info>Copied</info>]';

                    if (!$dry) {
                        $exists = is_file($dest);

                        if (!$force && $exists) {
                            $prefix = '[<comment>File exists</comment>]';
                        } else {
                            if ($exists) {
                                $prefix = '[<comment>Override</comment>]';
                            }

                            Filesystem::copy($src, $dest, $force);
                        }
                    }

                    $this->io->writeln(
                        sprintf(
                            '%s %s => <info>%s</info>',
                            $prefix,
                            Path::relative($root, $src),
                            Path::relative($root, $dest),
                        )
                    );
                }
            }
        }
    }

    protected function askForTargets(PackageRegistry $registry): array
    {
        $installer = $registry->getInstaller();

        $options = [];
        $items = [];

        $inputPackages = (array) $this->io->getArgument('packages');
        $packages = $registry->getPackages();

        if ($inputPackages !== []) {
            foreach ($packages as $i => $package) {
                if (!in_array($package::getName(), $inputPackages, true)) {
                    unset($packages[$i]);
                }
            }
        }

        foreach ($packages as $k => $package) {
            $name = $package::getName();

            $options[] = $name . ' ALL';
            $items[$name . ' ALL'] = [$name, null];

            foreach ($installer->getChild($name)->tags as $tag => $res) {
                $options[] = $optName = "<fg=gray>{$name}</>: {$tag}";
                $items[$optName] = [$name, $tag];
            }
        }

        if ($options === []) {
            throw new \UnexpectedValueException('No packages found.');
        }

        $qn = new ChoiceQuestion(
            '<question>Please select package or tags, use "," to select multiple options:</question>',
            $options
        );
        $qn->setMultiselect(true);

        $this->io->newLine();
        $selected = $this->io->ask($qn);

        $items = array_values(array_intersect_key($items, array_flip($selected)));

        $targets = [];

        foreach ($items as $item) {
            [$package, $tag] = $item;

            $targets[$package] ??= [];

            if ($tag !== null) {
                $targets[$package][] = $tag;
            }
        }

        return $targets;
    }

    protected function validateTargets(PackageRegistry $registry, array $packages, array $tags): void
    {
        $foundPackages = $registry->getPackages();

        $names = array_map(fn (AbstractPackage $pkg) => $pkg::getName(), $foundPackages);

        foreach ($packages as $package) {
            if (!in_array($package, $names, true)) {
                throw new InvalidArgumentException("Package: $package not found.");
            }
        }

        foreach ($packages as $package) {
            $installer = $registry->getInstaller()->getChild($package);

            foreach ($tags as $tag) {
                if (!isset($installer->tags[$tag])) {
                    throw new InvalidArgumentException("Package: $package has no tag: $tag.");
                }
            }
        }
    }
}
