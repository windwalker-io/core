<?php

declare(strict_types=1);

namespace Windwalker\Core\Package\Command;

use Composer\InstalledVersions;
use Composer\Semver\Comparator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Database\ORMAwareTrait;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Generator\FileCloner;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageRegistry;
use Windwalker\Core\Package\Record\PackageDbMigrate;
use Windwalker\Filesystem\Path;

use function Windwalker\chronos;
use function Windwalker\fs;

#[CommandWrapper(
    description: 'Package migrate command.',
    hidden: true
)]
class PackageMigrateCommand implements CommandInterface
{
    use ORMAwareTrait;

    public function __construct(protected PackageRegistry $registry)
    {
    }

    public function configure(Command $command): void
    {
        $command->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force to copy file even the file exists.'
        );

        $command->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Dry run the operation.'
        );

        $command->addOption(
            'keep-tmp',
            'k',
            InputOption::VALUE_NONE,
            'Keep the tmp upgrade file after operation.'
        );
    }

    public function execute(IOInterface $io): int
    {
        $force = $io->getOption('force');
        $dryRun = $io->getOption('dry-run');
        $keepTmp = $io->getOption('keep-tmp');

        $this->registry->discover();

        $composerJson = $this->registry->rootComposerJson;
        $tmpFile = $composerJson['extra']['windwalker']['upgrade-tmp'] ?? 'tmp/upgrades.json';
        $upgradeFile = fs(Path::makeAbsolute($tmpFile, WINDWALKER_ROOT));

        if (!$upgradeFile->isFile()) {
            $io->writeln('No upgrade-tmp file found.');

            return 0;
        }

        $upgradeJson = $upgradeFile->readAndParse();

        $expiredTime = chronos()->modify('-12hours');
        $upgradedTime = Chronos::createFromTimestamp($upgradeJson['time']);

        if ($expiredTime > $upgradedTime) {
            throw new \RuntimeException(
                'upgrade-tmp file expired. Please re-run `composer update` to restart a package migrating process.'
            );
        }

        $upgradedPackages = $upgradeJson['packages'] ?? [];

        $composerPackages = $this->registry->getComposerPackagesMap();

        $shouldUpgradePackages = array_intersect_key($composerPackages, $upgradedPackages);
        $migrator = $this->registry->prepareMigrate();

        $fileCloner = new FileCloner($io, dryRun: $dryRun);
        $dest = WINDWALKER_RESOURCES . '/migrations';
        $results = [];

        foreach ($shouldUpgradePackages as $upgradeComposerPackage => $packages) {
            if (!InstalledVersions::isInstalled($upgradeComposerPackage)) {
                throw new \RuntimeException(
                    sprintf('Package %s not installed. Please re-run `composer update`.', $upgradeComposerPackage)
                );
            }

            [$previousVersion, $currentVersion] = $upgradedPackages[$upgradeComposerPackage];

            // Do not handle when downgrade or same version.
            if (Comparator::compare($currentVersion, '<=', $previousVersion)) {
                continue;
            }

            // Find migration which can upgrade
            foreach ($packages as $package) {
                $subMigrator = $migrator->getChild($package::getName());

                foreach ($subMigrator->dbMigrates as $dbMigrate) {
                    // Check migration version in range.
                    if (!$this->isVersionInRange($dbMigrate, $previousVersion, $currentVersion)) {
                        continue;
                    }

                    // Check entities not overrides by App.
                    if (!$this->checkEntitiesNotOverrides($dbMigrate, $package)) {
                        continue;
                    }

                    foreach ($dbMigrate->migrationFiles as $migrationFile) {
                        $src = Path::normalize(Path::makeAbsolute($migrationFile, $package::root()));

                        $results[] = $fileCloner->copyFile($src, $dest, $force);
                    }
                }
            }
        }

        $fileCloner->printListResults($results);

        if ($keepTmp) {
            $io->style()->note('Keeping the tmp upgrade file not deleted: ' . $upgradeFile->getPathname());
        } else {
            $upgradeFile->deleteIfExists();
        }

        return 0;
    }

    protected function checkEntityNotOverride(string $entity, AbstractPackage $package): bool
    {
        $ref = new \ReflectionClass($entity);
        $shortName = $ref->getShortName();

        $appEntity = 'App\\Entity\\' . $shortName;

        // If App has override entity, skip it.
        if (class_exists($appEntity)) {
            return false;
        }

        $file = fs($ref->getFileName());

        // If entity is not in package src/Entity folder, skip it.
        // To prevent the class_alias or other files.
        if (!$file->isChildOf($package::path('src/Entity'))) {
            return false;
        }

        return true;
    }

    public function checkEntitiesNotOverrides(
        PackageDbMigrate $dbMigrate,
        AbstractPackage $package
    ): bool {
        $hasOverride = false;

        foreach ($dbMigrate->entities as $entityClass) {
            if (!$this->checkEntityNotOverride($entityClass, $package)) {
                $hasOverride = true;
                break;
            }
        }

        return !$hasOverride;
    }

    public function isVersionInRange(PackageDbMigrate $dbMigrate, mixed $previousVersion, mixed $currentVersion): bool
    {
        return Comparator::compare($dbMigrate->version, '>', $previousVersion)
            && Comparator::compare($dbMigrate->version, '<=', $currentVersion);
    }
}
