<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator;

use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Edge\Compiler\EdgeCompiler;
use Windwalker\Edge\Edge;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Iterator\FilesIterator;
use Windwalker\Utilities\Reflection\ReflectAccessor;

/**
 * The FileCollection class.
 */
class FileCollection implements EventAwareInterface
{
    use MessageOutputTrait;

    protected array $results = [];

    /**
     * FileCollection constructor.
     */
    public function __construct(protected FilesIterator $files)
    {
    }

    public function each(callable $callback): static
    {
        $edge = $this->createEdge();

        foreach ($this->files as $file) {
            $fileData = new FileData($file, $edge);
            $callback($fileData);
        }

        return $this;
    }

    public function replaceTo(string $destDir, callable|array $data, bool $force = false): static
    {
        $this->results = [];

        return $this->each(
            function (FileData $file) use ($force, $destDir, $data) {
                if (is_callable($data)) {
                    $data($file);
                } else {
                    $file->compileContent($data);
                }

                $isExists = $file->exists($destDir, $data);

                if ($force || !$isExists) {
                    $dest = $file->writeTo($destDir, $data);

                    $action = $isExists ? '<fg=cyan>OVERRIDE</>' : '<info>CREATE</info>';
                } else {
                    $dest = $file->compileDestFile($destDir, $data);
                    $action = '<comment>EXISTS</comment>';
                }

                $this->results[] = $dest;

                $this->emitMessage("[$action] " . $dest->getRelativePathname(WINDWALKER_ROOT));
            }
        );
    }

    protected function createEdge(): Edge
    {
        $compiler = new EdgeCompiler();
        $compiler->setContentTags('{%', '%}');
        $compiler->setRawTags('{%', '%}');

        ReflectAccessor::setValue(
            $compiler,
            'compilers',
            [
                'Parsers',
                'Comments',
                'Echos',
            ]
        );

        $edge = new Edge(null, null, $compiler);

        $edge->addExtension(new GeneratorEdgeExtension());

        return $edge;
    }

    /**
     * @return FilesIterator
     */
    public function getFiles(): FilesIterator
    {
        return $this->files;
    }

    /**
     * @param  FilesIterator  $files
     *
     * @return  static  Return self to support chaining.
     */
    public function setFiles(FilesIterator $files): static
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @return array<FileObject>
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
