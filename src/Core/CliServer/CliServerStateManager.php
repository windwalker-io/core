<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use Windwalker\Filesystem\FileObject;

use function Windwalker\collect;
use function Windwalker\fs;

/**
 * The CliSeverStateLoader class.
 */
class CliServerStateManager
{
    public function __construct(protected $filePath = '')
    {
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @param  string  $filePath
     *
     * @return  static  Return self to support chaining.
     */
    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getFile(): FileObject
    {
        return fs($this->filePath);
    }

    public function getState(): CliServerState
    {
        $stateFile = $this->getFile();

        if (!$stateFile->isFile()) {
            $state = collect();
        } else {
            $state = $stateFile->readAndParse('json');
        }

        return CliServerState::wrap($state);
    }

    public function createState(string $host, int $port, array $options): CliServerState
    {
        $state = $this->getState();

        $state->setName($options['process_name']);
        $state->setHost($host);
        $state->setPort($port);
        $state->setManagerOptions($options);

        $this->writeState($state);

        return $state;
    }

    public function writeState(CliServerState $state): FileObject
    {
        return $this->getFile()->write(
            json_encode($state, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }
}
