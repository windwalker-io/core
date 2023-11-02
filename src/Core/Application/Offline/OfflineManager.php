<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application\Offline;

use Windwalker\Filesystem\FileObject;

use function Windwalker\fs;

/**
 * The OfflineManager class.
 */
class OfflineManager
{
    public function makeOffline(OfflineConfig|array $config): void
    {
        $config = OfflineConfig::wrap($config);

        $this->getFile()->write(
            json_encode($config, JSON_PRETTY_PRINT)
        );
    }

    public function makeOnline(): void
    {
        $this->getFile()->deleteIfExists();
    }

    public function isOffline(): bool
    {
        return $this->getFile()->isFile();
    }

    public function getPayload(): OfflineConfig
    {
        return OfflineConfig::wrap($this->getFile()->readAndParse('json'));
    }

    public function getFilePath(): string
    {
        return WINDWALKER_TEMP . '/offline.json';
    }

    public function getFile(): FileObject
    {
        return fs($this->getFilePath());
    }
}
