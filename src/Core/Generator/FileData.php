<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator;

use Psr\Http\Message\StreamInterface;
use Windwalker\Edge\Compiler\EdgeCompiler;
use Windwalker\Edge\Edge;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Stream\StringStream;
use Windwalker\Utilities\Str;

/**
 * The FileData class.
 */
class FileData
{
    protected FileObject $src;

    protected ?FileObject $dest = null;

    protected ?StreamInterface $destStream = null;

    protected string $destFilePath = '';

    /**
     * FileData constructor.
     *
     * @param  FileObject  $src
     */
    public function __construct(FileObject $src, protected Edge $edge)
    {
        $this->src = $src;
    }

    public function compileContent(array $data = []): static
    {
        $srcStream = $this->getSrcStream();

        $destString = $this->compile((string) $srcStream, $data);
        $destStream = new StringStream($destString);

        $this->destStream = $destStream;

        return $this;
    }

    public function compileDestFile(string $destDir, array $data): FileObject
    {
        if ($this->dest) {
            return $this->dest;
        }

        $path = $this->src->getRelativePath();
        $destPath = $this->compile($path, $data);

        $this->destFilePath = Str::removeRight($destPath, '.tpl');

        return $this->dest = new FileObject($destDir . '/' . $this->destFilePath, $destPath);
    }

    protected function compile(string $string, array $data): string
    {
        /** @var EdgeCompiler $compiler */
        $compiler = $this->edge->getCompiler();
        $string = $compiler->compileEchos($string);

        $data['phpOpen'] = '<?php';
        $data['phpClose'] = '?>';

        return $this->edge->render($string, $data);
    }

    public function exists(string $destDir, array $data = []): bool
    {
        $dest = $this->compileDestFile($destDir, $data);

        return is_file($dest->getPathname());
    }

    public function writeTo(string $destDir, array $data = []): FileObject
    {
        $dest = $this->compileDestFile($destDir, $data);

        Filesystem::mkdir($dest->getPath());

        return Filesystem::write($dest->getPathname(), (string) $this->destStream);
    }

    /**
     * @return FileObject
     */
    public function getSrc(): FileObject
    {
        return $this->src;
    }

    /**
     * @param  FileObject  $src
     *
     * @return  static  Return self to support chaining.
     */
    public function setSrc(FileObject $src): static
    {
        $this->src = $src;

        return $this;
    }

    /**
     * @return FileObject|null
     */
    public function getDest(): ?FileObject
    {
        return $this->dest;
    }

    /**
     * @param  FileObject|null  $dest
     *
     * @return  static  Return self to support chaining.
     */
    public function setDest(?FileObject $dest): static
    {
        $this->dest = $dest;

        return $this;
    }

    public function getSrcStream(): StreamInterface
    {
        return $this->src->getStream();
    }

    /**
     * @return StreamInterface|null
     */
    public function getDestStream(): ?StreamInterface
    {
        return $this->destStream;
    }
}
