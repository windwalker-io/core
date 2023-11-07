<?php

declare(strict_types=1);

namespace Windwalker\Core\Utilities;

use Composer\Autoload\ClassLoader;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\StrNormalize;

/**
 * The ClassFinder class.
 */
class ClassFinder
{
    /**
     * ClassFinder constructor.
     */
    public function __construct(protected PathResolver $pathResolver, protected ClassLoader $loader)
    {
    }

    public function findDirsFromNamespace(string $ns): array
    {
        $dirs = [];

        foreach ($this->loader->getPrefixesPsr4() as $prefix => $paths) {
            if (str_starts_with($ns, $prefix)) {
                foreach ($paths as $path) {
                    $dir = Path::normalize($path);
                    $p = Str::removeLeft($ns, $prefix, 'ascii');
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
    public function findFiles(string $ns, bool $recursive = false): iterable
    {
        $dirs = $this->findDirsFromNamespace($ns);

        $dirs = array_map(
            static fn($dir) => $recursive
                ? $dir . '/**/*.php'
                : $dir . '/*.php',
            $dirs
        );

        return Filesystem::globAll($dirs);
    }

    public function findClasses(string $ns, bool $recursive = false): iterable
    {
        $files = $this->findFiles($ns, $recursive);

        foreach ($files as $file) {
            if (str_contains($file->getFilename(), '.blade.')) {
                continue;
            }

            if ($recursive) {
                $class = Path::stripExtension($file->getRelativePathname());
            } else {
                $class = $file->getBasename('.php');
            }

            yield StrNormalize::toClassNamespace($ns . '\\' . $class);
        }
    }
}
