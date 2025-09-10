<?php

declare(strict_types=1);

namespace Windwalker\Core\Package\Command;

use Symfony\Component\Console\Command\Command;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Database\ORMAwareTrait;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageRegistry;

use function Windwalker\fs;

#[CommandWrapper(
    description: 'Package migrate command.',
)]
class PackageMigrateCommand implements CommandInterface
{
    use ORMAwareTrait;

    public function __construct(protected PackageRegistry $registry)
    {
    }

    public function configure(Command $command): void
    {
        //
    }

    public function execute(IOInterface $io): int
    {
        $packages = $this->registry->getPackages();
        $migrator = $this->registry->prepareMigrate();

        $entities = [];

        foreach ($packages as $package) {
            $subMigrator = $migrator->getChild($package::getName());

            foreach ($subMigrator->entities as $entityClass) {
                if ($this->canMigrate($entityClass, $package)) {
                    $diff = $this->diffColumns($entityClass);

                    $entities[$entityClass] = $diff;
                }
            }
        }

        return 0;
    }

    protected function canMigrate(string $entity, AbstractPackage $package): bool
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

    protected function diffColumns(string $entity): array
    {
        $metadata = $this->orm->getEntityMetadata($entity);

        $attrColumnNames = array_keys($metadata->getPureColumns());

        $dbColumnNames = $this->orm->db->getTableManager($entity)->getColumnNames(true);

        return array_map(
            static fn ($col) => $metadata->getColumn($col),
            array_values(array_diff($attrColumnNames, $dbColumnNames))
        );
    }
}
