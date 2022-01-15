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

        $file = $dir . '/' . $id;

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
            $files = $this->getFiles();

            if (!$files) {
                return [];
            }

            $items = [];

            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                [$basicData, $data] = explode(static::FILE_SEPARATOR, file_get_contents($file->getPathname()));

                $item = json_decode($basicData, true);

                $item['id'] = $file->getBasename();

                if ($includeData) {
                    $item['data'] = $data;
                }

                $items[$file->getMTime()] = $item;
            }

            krsort($items);

            return array_slice($items, 0, $limit);
        });
    }

    /**
     * getFiles
     *
     * @return  RecursiveIteratorIterator
     */
    public function getFiles(): iterable
    {
        $dir = $this->getCacheFolder();

        if (!is_dir($dir)) {
            return [];
        }

        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );
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
            'ip' => $collector->getDeep('system.remoteIP'),
        ];

        $content = implode(
            static::FILE_SEPARATOR,
            [
                json_encode($basicData),
                json_encode($collector),
            ]
        );

        return Filesystem::write(
            $this->getCacheFolder() . '/' . $id,
            $content
        );
    }

    /**
     * deleteOldFiles
     *
     * @param  int  $maxFiles
     *
     * @return  void
     */
    public function deleteOldFiles(int $maxFiles = 100): void
    {
        $files = $this->getFiles();
        $items = [];

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (is_file($file->getPathname())) {
                @$items[$file->getMTime()] = $file;
            }
        }

        krsort($items);

        $i = 0;

        /** @var SplFileInfo $file */
        foreach ($items as $file) {
            $i++;

            if ($i < $maxFiles) {
                continue;
            }

            if (is_file($file->getPathname())) {
                try {
                    @Filesystem::delete($file->getPathname());
                } catch (Throwable $e) {
                    // Ignore error
                }
            }
        }
    }

    /**
     * getCacheFolder
     *
     * @return  string
     */
    protected function getCacheFolder(): string
    {
        return WINDWALKER_CACHE . '/profiler';
    }
}
