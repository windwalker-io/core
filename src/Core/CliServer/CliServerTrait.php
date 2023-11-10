<?php

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use Symfony\Component\Process\PhpExecutableFinder;
use Windwalker\Core\Filesystem\FileWatcher;

/**
 * Trait CliServerTrait
 */
trait CliServerTrait
{
    protected function getPhpBinary(): false|string
    {
        return (new PhpExecutableFinder())->find();
    }

    public function createFileWatcher(string $mainFile): FileWatcher
    {
        $paths = (array) $this->app->config('reactor.watch');

        $paths[] = $mainFile;

        return FileWatcher::paths(...$paths)
                ->usePolling(false)
                ->awaitWriteFinish();
    }
}
