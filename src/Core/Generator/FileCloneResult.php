<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator;

use Psr\Http\Message\StreamInterface;
use Windwalker\Filesystem\FileObject;

class FileCloneResult
{
    public ?FileObject $dest = null;

    public ?StreamInterface $destStream = null;

    public string $destFilePath = '';

    public int $action;

    public bool $dryRun = false;

    public function __construct(public FileObject $src)
    {
        //
    }
}
