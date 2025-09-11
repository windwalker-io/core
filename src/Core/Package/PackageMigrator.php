<?php

declare(strict_types=1);

namespace Windwalker\Core\Package;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Package\Record\PackageDbMigrate;
use Windwalker\Core\Utilities\ClassFinder;
use Windwalker\Filesystem\Filesystem;

class PackageMigrator
{
    public protected(set) array $children = [];

    /**
     * @var array<PackageDbMigrate>
     */
    public protected(set) array $dbMigrates = [];

    public function __construct(public readonly ?string $name, protected ApplicationInterface $app)
    {
    }

    public function getChild(string $name): static
    {
        return $this->children[$name] ??= new static($name, $this->app);
    }

    public function addDbMigrate(string $version): PackageDbMigrate
    {
        $this->dbMigrates[] = $migrate = new PackageDbMigrate($version);

        return $migrate;
    }
    //
    // public function migrateEntities(
    //     string ...$entities
    // ): static {
    //     $this->entities = array_merge($this->entities, array_values($entities));
    //
    //     return $this;
    // }
    //
    // public function migrateEntitiesFromPath(string $path, string $ns): static
    // {
    //     if (str_contains($path, '*')) {
    //         $files = Filesystem::glob($path);
    //     } else {
    //         $files = Filesystem::files($path);
    //     }
    //
    //     foreach ($files as $file) {
    //         if ($file->getExtension() !== 'php') {
    //             continue;
    //         }
    //
    //         $class = $ns . '\\' . $file->getBasename('.php');
    //
    //         if (class_exists($class)) {
    //             $this->entities[] = $class;
    //         }
    //     }
    //
    //     $this->entities = array_unique($this->entities);
    //
    //     return $this;
    // }
    //
    // public function migrateEntitiesByNamespace(string $ns): static
    // {
    //     $finder = $this->app->make(ClassFinder::class);
    //     $classes = $finder->findClasses($ns);
    //
    //     foreach ($classes as $class) {
    //         $this->entities[] = $class;
    //     }
    //
    //     return $this;
    // }
    //

}
