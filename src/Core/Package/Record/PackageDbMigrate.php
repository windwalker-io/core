<?php

declare(strict_types=1);

namespace Windwalker\Core\Package\Record;

class PackageDbMigrate
{
    public array $migrationFiles = [];

    public array $entities = [];

    public function __construct(public string $version = '') {}

    /**
     * @codeCoverageIgnore
     */
    public function getMigrationFiles(): array
    {
        return $this->migrationFiles;
    }

    /**
     * @codeCoverageIgnore
     */
    public function migrationFiles(string ...$migrationFiles): static
    {
        $this->migrationFiles = $migrationFiles;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getEntitiesShouldChecks(): array
    {
        return $this->entities;
    }

    /**
     * @codeCoverageIgnore
     */
    public function checkEntitiesNotOverrides(string ...$entities): static
    {
        $this->entities = $entities;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getVersion(): string
    {
        return $this->version;
    }
}
