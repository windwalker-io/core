<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator;

use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Console\IOInterface;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Stream\StringStream;

use function Windwalker\fs;

class FileCloner
{
    public const int CREATE = 1;

    public const int IGNORE = 2;

    public const int OVERRIDE = 3;

    public const int OUTPUT_QUIET = 0;

    public const int OUTPUT_SUMMARY = 1;

    public const int OUTPUT_CHANGED = 2;

    public const int OUTPUT_ALL = 3;

    public OutputInterface $output;

    public function __construct(
        IOInterface|OutputInterface $output,
        public ?\Closure $replacer = null,
        public bool $link = false,
        public bool $dryRun = false,
        public bool $printSourcePath = false,
        public ?int $verbosity = null {
            get => $this->verbosity ?? $this->output->getVerbosity();
        },
    ) {
        if ($output instanceof IOInterface) {
            $this->output = $output->getOutput();
        } else {
            $this->output = $output;
        }
    }

    /**
     * @param  iterable<string|FileObject>|string  $files
     * @param  string|FileObject                   $dest
     * @param  bool                                $force
     *
     * @return array{ FileCloneResult[], FileCloneResult[], FileCloneResult[] }
     */
    public function copyList(iterable|string $files, FileObject|string $dest, bool $force = false): array
    {
        $results = [];

        if (is_string($files)) {
            $files = Filesystem::glob($files);
        }

        foreach ($files as $file) {
            $result = $this->copyFile($file, $dest, $force);
            $results[] = $result;
        }

        $this->printListResults($results);

        return $results;
    }

    /**
     * @param  string|FileObject  $src
     * @param  string|FileObject  $dest
     * @param  bool               $force
     *
     * @return FileCloneResult
     */
    public function copyFile(string|FileObject $src, string|FileObject $dest, bool $force = false): FileCloneResult
    {
        if (is_string($src)) {
            $src = fs($src);
        }

        if ($src->isDir()) {
            throw new \InvalidArgumentException(
                "Source: {$src} cannot be directory."
            );
        }

        $dest = fs($dest);

        if ($dest->isDir()) {
            $dest = $dest->appendPath('/' . $src->getFilename());
        }

        $isExists = $dest->exists();
        $result = new FileCloneResult($src);
        $result->dest = $dest;

        if ($force || !$isExists) {
            if (!$this->dryRun) {
                if ($this->link) {
                    $dest->deleteIfExists();

                    $dest->getParent()->mkdir();

                    Filesystem::symlink($src->getPathname(), $dest->getPathname());
                } else {
                    $srcData = (string) $src->read();

                    $destData = $this->replacer ? ($this->replacer)($srcData, $src, $dest) : $srcData;

                    $dest->write($destData);

                    $result->destStream = new StringStream($destData);
                }
            }

            $result->action = $isExists ? static::OVERRIDE : static::CREATE;
        } else {
            $result->action = static::IGNORE;
        }

        $result->dryRun = $this->dryRun;

        if ($this->getOutputLevel() === static::OUTPUT_ALL) {
            $this->printSingleResult($result);
        }

        return $result;
    }

    public function printListResults(iterable $results, ?int $outputLevel = null): void
    {
        $results = iterator_to_array($results);

        [$creates, $ignores, $overrides] = static::splitResultActions($results);

        $outputLevel ??= $this->getOutputLevel();

        if ($outputLevel === static::OUTPUT_CHANGED) {
            foreach ($results as $result) {
                if ($result->action === static::IGNORE) {
                    continue;
                }

                $this->printSingleResult($result);
            }
        }

        if ($outputLevel >= static::OUTPUT_SUMMARY) {
            $this->printSummary($creates, $ignores, $overrides);
        }
    }

    public function printSingleResult(FileCloneResult $result): void
    {
        $prefix = match ($result->action) {
            static::CREATE => '<info>CREATE</info>',
            static::IGNORE => '<comment>EXISTS</comment>',
            static::OVERRIDE => '<fg=cyan>OVERRIDE</>',
            default => 'Unknown',
        };

        $this->output->writeln(
            sprintf(
                '[%s] %s<info>%s</info>',
                $prefix,
                $this->printSourcePath ? $result->src->getRelativePathname(WINDWALKER_ROOT) . ' -> ' : '',
                $result->dest->getRelativePathname(WINDWALKER_ROOT)
            )
        );
    }

    public function printSummary(array $creates, array $ignores, array $overrides): void
    {
        $this->output->writeln(
            sprintf(
                'Files: Created: <info>%d</info>, Exists: <comment>%d</comment>, Overridden: <fg=cyan>%d</>',
                count($creates),
                count($ignores),
                count($overrides)
            )
        );
    }

    /**
     * @param  iterable  $results
     *
     * @return  array{ FileCloneResult[], FileCloneResult[], FileCloneResult[] }
     */
    public static function splitResultActions(iterable $results): array
    {
        $results = iterator_to_array($results);

        $creates = array_filter($results, static fn(FileCloneResult $r) => $r->action === static::CREATE);
        $ignores = array_filter($results, static fn(FileCloneResult $r) => $r->action === static::IGNORE);
        $overrides = array_filter($results, static fn(FileCloneResult $r) => $r->action === static::OVERRIDE);

        return [$creates, $ignores, $overrides];
    }

    public function getOutputLevel(): int
    {
        return match ($this->verbosity) {
            static::OUTPUT_QUIET,
            static::OUTPUT_SUMMARY,
            static::OUTPUT_CHANGED,
            static::OUTPUT_ALL => $this->verbosity,
            OutputInterface::VERBOSITY_QUIET => static::OUTPUT_QUIET,
            OutputInterface::VERBOSITY_NORMAL => static::OUTPUT_SUMMARY,
            OutputInterface::VERBOSITY_VERBOSE => static::OUTPUT_CHANGED,
            OutputInterface::VERBOSITY_VERY_VERBOSE, OutputInterface::VERBOSITY_DEBUG => static::OUTPUT_ALL,
            default => static::OUTPUT_SUMMARY,
        };
    }
}
