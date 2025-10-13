<?php

declare(strict_types=1);

namespace Windwalker\Core\Asset\Command;

use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\Intervals;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use JsonException;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Attributes\AttributesAccessor;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\Input\InputOption;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Attributes\ViewModel;
use Windwalker\Core\Package\PackageRegistry;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\Str;

use function Windwalker\fs;

/**
 * The AssetSyncCommand class.
 */
#[CommandWrapper(description: 'Sync vendor assets dependencies to package.json', hidden: true)]
class AssetSyncCommand implements CommandInterface
{
    /**
     * AssetSyncCommand constructor.
     *
     * @param  ApplicationInterface  $app
     * @param  PackageRegistry       $packageRegistry
     */
    public function __construct(
        protected ApplicationInterface $app,
        protected PackageRegistry $packageRegistry,
    ) {
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
        //
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     * @throws JsonException
     */
    public function execute(IOInterface $io): int
    {
        $packages = $this->packageRegistry->getPackages();
        $packageJsonFile = fs(WINDWALKER_ROOT . '/package.json');

        if (!$packageJsonFile->isFile()) {
            throw new \RuntimeException('package.json not exists to sync.');
        }

        $io->writeln('Sync package.json Versions');

        $packageJson = $packageJsonFile->readAndParse('json');

        $versionParser = new VersionParser();

        $override = false;

        foreach ($packages as $package) {
            $json = $package::composerJson();

            if (!$json) {
                continue;
            }

            $vendors = $json['extra']['windwalker']['assets']['vendors'] ?? [];

            foreach ($vendors as $vendor => $versions) {
                if (
                    str_starts_with($versions, 'portal:')
                    || str_starts_with($versions, 'file:')
                    || str_starts_with($versions, 'link:')
                ) {
                    $packageJson->setDeep(
                        'dependencies#' . $vendor,
                        $versions,
                        '#'
                    );

                    $override = true;

                    $io->writeln("Local Link: <info>\"$vendor\"</info> to \"$versions\"");
                    continue;
                }

                $constraints = $versionParser->parseConstraints($versions);

                if ($currentVersions = $packageJson->getDeep('dependencies#' . $vendor, '#')) {
                    $currentConstraints = $versionParser->parseConstraints($currentVersions);

                    if (!Intervals::isSubsetOf($constraints, $currentConstraints)) {
                        $packageJson->setDeep(
                            'dependencies#' . $vendor,
                            $newVersion = $currentVersions . '|' . $versions,
                            '#'
                        );

                        $override = true;

                        $io->writeln("Replace: <info>\"$vendor\"</info> version to \"$newVersion\"");
                    }
                } else {
                    $packageJson->setDeep(
                        'dependencies#' . $vendor,
                        $versions,
                        '#'
                    );

                    $override = true;

                    $io->writeln("Add: <info>\"$vendor\"</info>: \"$versions\"");
                }
            }
        }

        if ($override) {
            $packageJsonFile->write(
                json_encode(
                    $packageJson,
                    JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ) . "\n"
            );

            $io->newLine();
            $io->writeln('package.json file modified.');
        } else {
            $io->writeln('package.json not changed.');
        }

        return 0;
    }
}
