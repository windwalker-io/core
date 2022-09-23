<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Debugger\Module\Dashboard;

use FilesystemIterator;
use Psr\Cache\InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;
use Windwalker\Data\Collection;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

use function Windwalker\fs;

/**
 * The DashboardRepository class.
 */
class DashboardRepository
{
    use InstanceCacheTrait;

    public const FILE_SEPARATOR = '{{ Debugger data ---------- }}';

    public function getItem(string $id): array
    {
        $dir = $this->getCacheFolder();

        $file = $dir->appendPath($id);

        if (!is_file($file)) {
            throw new RuntimeException('ID ' . $id . ' not found.');
        }

        [$basicData, $data] = explode(static::FILE_SEPARATOR, file_get_contents($file));

        return json_decode($data, true);
    }

    /**
     * getItems
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function getItems(int $limit = 100, bool $includeData = false): array
    {
        return $this->once('items', function () use ($includeData, $limit) {
            $folders = $this->getFilesOrFolders();

            if (!$folders) {
                return [];
            }

            $items = [];

            /** @var FileObject $folder */
            foreach ($folders as $folder) {
                $basicData = $folder->appendPath('/basic.json')->read()->jsonDecode();

                $items[$folder->getMTime()] = $basicData;
            }

            krsort($items);

            return array_slice($items, 0, $limit);
        });
    }

    /**
     * getFiles
     *
     * @return  iterable<FileObject>
     */
    public function getFilesOrFolders(): iterable
    {
        $dir = $this->getCacheFolder();

        if (!$dir->isDir()) {
            return [];
        }

        return $dir->items();
    }

    /**
     * getLastItem
     *
     * @return  array
     * @throws InvalidArgumentException
     */
    public function getLastItem(): array
    {
        $items = $this->getItems();

        return array_shift($items);
    }

    public function writeFile(string $id, Collection $collector): FileObject
    {
        $basicData = [
            'id' => $id,
            'url' => $collector->getDeep('http.systemUri')?->full,
            'method' => $collector->getDeep('http.request.method'),
            'response' => $collector->getDeep('http.response'),
            'time' => microtime(true),
            'ip' => $collector->getDeep('http.remoteIP'),
        ];

        $folder = $this->getCacheFolder()->appendPath('/' . $id);

        $folder->appendPath('/basic.json')->write(json_encode($basicData));
        $folder->appendPath('/db.json')->write(json_encode($collector['db']));
        $folder->appendPath('/system.json')->write(json_encode($collector['system']));
        $folder->appendPath('/routing.json')->write(json_encode($collector['routing']));
        $folder->appendPath('/http.json')->write(json_encode($collector['http']));

        return $folder;
    }

    /**
     * deleteOldFiles
     *
     * @param  int  $maxFiles
     *
     * @return  void
     */
    public function deleteOldRecords(int $maxFiles = 100): void
    {
        $folders = $this->getFilesOrFolders();
        $items = [];

        /** @var FileObject $folder */
        foreach ($folders as $folder) {
            @$items[$folder->getMTime()] = $folder;
        }

        krsort($items);

        $i = 0;

        /** @var FileObject $folder */
        foreach ($items as $folder) {
            $i++;

            if ($i < $maxFiles) {
                continue;
            }

            try {
                @$folder->deleteIfExists();
            } catch (Throwable $e) {
                // Ignore error
            }
        }
    }

    /**
     * getCacheFolder
     *
     * @return  FileObject
     */
    protected function getCacheFolder(): FileObject
    {
        return fs(WINDWALKER_CACHE . '/profiler');
    }
}
