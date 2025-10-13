<?php

declare(strict_types=1);

namespace Windwalker\Core\Package\Command;

use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use UnexpectedValueException;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Generator\FileCloner;
use Windwalker\Core\Generator\FileCloneResult;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\InstallResource;
use Windwalker\Core\Package\PackageRegistry;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;

/**
 * The PackageInstallCommand class.
 */
#[CommandWrapper(description: 'Install package resources.')]
class PackageInstallCommand implements CommandInterface, CompletionAwareInterface
{
    /**
     * @var IOInterface
     */
    private ?IOInterface $io = null;

    protected FileCloner $fileCloner;

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
        $command->addOption(
            'link',
            'l',
            InputOption::VALUE_OPTIONAL,
            'Force override files.',
            false
        );
        $command->addOption(
            'details',
            null,
            InputOption::VALUE_REQUIRED,
            'Show details of each file operation.',
            3
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
        $details = (int) $this->io->getOption('details');

        $registry = $this->app->make(PackageRegistry::class);

        // Discover and prepare-install
        $installer = $registry->prepareInstall();

        if (!$tags) {
            $targets = $this->askForTargets($registry);
        } else {
            $targets = [];

            if (!$packages) {
                foreach ($registry->getPackages() as $package) {
                    $tagNames = array_keys($installer->getChild($package)->tags);

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

        $resultSet = [];

        if ($details < 2) {
            $io->writeln("Installing selected resources...");
        }

        // Install
        foreach ($targets as $package => $tags) {
            $pkgInstaller = $installer->getChild($registry->getPackage($package));

            $callbacks = $pkgInstaller->getAllCallbacks($tags);

            if ($details > 2) {
                $io->writeln("Installing: <comment>$package</comment>");
            }

            if ($tags === []) {
                $resultSet[] = $this->install($pkgInstaller->installResources, $callbacks);
            } else {
                foreach ($tags as $tag) {
                    if (!isset($pkgInstaller->tags[$tag])) {
                        continue;
                    }

                    $resultSet [] = $this->install($pkgInstaller->tags[$tag], $callbacks);
                }
            }
        }

        $results = array_merge(...$resultSet);

        $this->getFilCloner()->printListResults($results);

        return 0;
    }

    /**
     * @param  InstallResource  $installResource
     * @param  array            $callbacks
     *
     * @return  array<FileCloneResult>
     */
    protected function install(InstallResource $installResource, array $callbacks): array
    {
        // $root = $this->app->path('@root');
        // $dry = $this->io->getOption('dry-run') !== false;
        $force = $this->io->getOption('force') !== false;

        $filCloner = $this->getFilCloner();
        $results = [];

        foreach ($installResource->dump() as $files) {
            if ($files !== []) {
                foreach ($files as $src => $dest) {
                    $results[] = $result = $filCloner->copyFile($src, $dest, $force);

                    if (!$result->dryRun && $result->action !== $filCloner::IGNORE) {
                        foreach ($callbacks as $callback) {
                            $this->app->call(
                                $callback,
                                [
                                    'src' => $src,
                                    'dest' => $dest,
                                    'force' => $force,
                                    'io' => $this->io,
                                    IOInterface::class => $this->io,
                                    'command' => $this,
                                    Command::class => $this,
                                ]
                            );
                        }
                    }

                    if ($filCloner->getOutputLevel() === 3) {
                        $filCloner->printSingleResult($result);
                    }
                }
            }
        }

        return $results;
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

            foreach ($installer->getChild($package)->tags as $tag => $res) {
                $options[] = $optName = "<fg=gray>{$name}</>: {$tag}";
                $items[$optName] = [$name, $tag];
            }
        }

        if ($options === []) {
            throw new UnexpectedValueException('No packages found.');
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

    /**
     * @param  PackageRegistry  $registry
     * @param  string[]          $packages
     * @param  string[]          $tags
     *
     * @return  void
     *
     * @throws \JsonException
     */
    protected function validateTargets(PackageRegistry $registry, array $packages, array $tags): void
    {
        $foundPackages = $registry->getPackages();

        $names = array_map(fn(AbstractPackage $pkg) => $pkg::getName(), $foundPackages);

        foreach ($packages as $package) {
            if (!in_array($package, $names, true)) {
                throw new InvalidArgumentException("Package: $package not found.");
            }
        }

        foreach ($packages as $package) {
            $installer = $registry->getInstaller()->getChild($registry->getPackage($package));

            foreach ($tags as $tag) {
                if (!isset($installer->tags[$tag])) {
                    $this->io->style()->warning("Package: $package has no tag: $tag.");
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function completeOptionValues($optionName, CompletionContext $context)
    {
        if ($optionName === 'tag') {
            $registry = $this->app->make(PackageRegistry::class);

            $installer = $registry->prepareInstall();

            $tags = [];

            foreach ($registry->getPackages() as $package) {
                $tags[] = array_keys($installer->getChild($package)->tags);
            }

            return array_unique(array_merge(...$tags));
        }
    }

    /**
     * @inheritDoc
     */
    public function completeArgumentValues($argumentName, CompletionContext $context)
    {
        if ($argumentName === 'packages') {
            $registry = $this->app->make(PackageRegistry::class);

            return array_map(static fn (AbstractPackage $pkg) => $pkg::getName(), $registry->getPackages());
        }
    }

    public function getFilCloner(): FileCloner
    {
        $dry = $this->io->getOption('dry-run') !== false;
        $details = (int) $this->io->getOption('details');

        return $this->fileCloner ??= new FileCloner(
            output: $this->io,
            link: $this->io->getOption('link') !== false,
            dryRun: $dry,
            printSourcePath: true,
            verbosity: $details,
        );
    }
}
