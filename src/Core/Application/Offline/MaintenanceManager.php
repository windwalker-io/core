<?php

declare(strict_types=1);

namespace Windwalker\Core\Application\Offline;

use Windwalker\Filesystem\FileObject;

use function Windwalker\fs;

/**
 * The MaintenanceManager class.
 */
class MaintenanceManager
{
    public function down(MaintenanceConfig|array $config): void
    {
        $config = MaintenanceConfig::wrap($config);

        $this->getFile()->write(
            json_encode($config, JSON_PRETTY_PRINT)
        );
    }

    public function up(): void
    {
        $this->getFile()->deleteIfExists();
    }

    public function isDown(): bool
    {
        return $this->getFile()->isFile();
    }

    public function getConfig(): MaintenanceConfig
    {
        return MaintenanceConfig::wrap($this->getFile()->readAndParse('json'));
    }

    public function getFilePath(): string
    {
        return WINDWALKER_TEMP . '/down.json';
    }

    public function getFile(): FileObject
    {
        return fs($this->getFilePath());
    }
}
