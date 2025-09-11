<?php

declare(strict_types=1);

namespace Windwalker\Core\Package\Record;

class PackageDbMigrate
{
    public array $migrationFiles = [];

    public array $entities = [];

    public function __construct(public string $version = '')
    {
    }

    public function getMigrationFiles(): array
    {
        return $this->migrationFiles;
    }

    public function migrationFiles(string ...$migrationFiles): static
    {
        $this->migrationFiles = $migrationFiles;

        return $this;
    }

    public function getEntitiesShouldChecks(): array
    {
        return $this->entities;
    }

    public function checkEntitiesNotOverrides(string ...$entities): static
    {
        $this->entities = $entities;

        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
