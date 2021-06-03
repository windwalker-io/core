<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Utilities;

use Composer\Autoload\ClassLoader;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\Str;

/**
 * The ClassFinder class.
 */
class ClassFinder
{
    /**
     * ClassFinder constructor.
     */
    public function __construct(protected PathResolver $pathResolver)
    {
    }

    public function findDirsFromNamespace(string $ns): array
    {
        /** @var ClassLoader $loader */
        $loader = include $this->pathResolver->resolve('@root/vendor/autoload.php');

        $dirs = [];

        foreach ($loader->getPrefixesPsr4() as $prefix => $paths) {
            if (str_starts_with($ns, $prefix)) {
                foreach ($paths as $path) {
                    $dir = Path::normalize($path);
                    $p = Str::removeLeft($ns, $prefix);
                    $dir .= '/' . $p;

                    $dirs[] = $dir;
                }
            }
        }

        return $dirs;
    }

    /**
     * findFiles
     *
     * @param  string  $ns
     *
     * @return  iterable<FileObject>
     */
    public function findFiles(string $ns): iterable
    {
        $dirs = $this->findDirsFromNamespace($ns);
        $dirs = array_map(
            fn ($dir) => $dir . '/*.php',
            $dirs
        );

        return Filesystem::globAll($dirs);
    }

    public function findClasses(string $ns): iterable
    {
        $files = $this->findFiles($ns);

        foreach ($files as $file) {
            $class = $file->getBasename('.php');

            yield $ns . '\\' . $class;
        }
    }
}
