<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Seed;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Core\Generator\CodeGenerator;
use Windwalker\Core\Generator\FileCollection;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Filesystem\FileObject;
use Windwalker\Utilities\StrNormalize;
use Windwalker\Utilities\TypeCast;

/**
 * The SeedService class.
 */
class SeedService implements EventAwareInterface
{
    use MessageOutputTrait;

    /**
     * MigrationService constructor.
     *
     * @param  ApplicationInterface  $app
     * @param  DatabaseAdapter       $db
     * @param  FakerService          $fakerService
     */
    public function __construct(
        protected ApplicationInterface $app,
        protected DatabaseAdapter $db,
        protected FakerService $fakerService
    ) {
        //
    }

    public function import(string|\SplFileInfo $file): int
    {
        $entry = FileObject::wrap($file);

        $seeders = $this->includeList($entry);

        $count = 0;

        foreach ($seeders as $seederFile) {
            $seeder = new Seeder(
                FileObject::wrap($seederFile),
                $this->db,
                $this->fakerService
            );
            $db     = $seeder->db;
            $orm    = $db->orm();
            $app    = $this->app;

            include $seederFile;

            if (is_callable($seeder->import)) {
                $seeder->addEventDealer($this);

                $this->emitMessage(
                    "Import seeder: <info>{$seeder->prettyName}</info> (<fg=gray>/{$seeder->file->getBasename()}</>)"
                );

                $this->app->call($seeder->import);

                if ($seeder->count > 0) {
                    $this->emitMessage('');
                }

                $this->emitMessage('  <comment>Import completed...</comment>');

                $count++;
            }
        }

        return $count;
    }

    public function clear(string|\SplFileInfo $file): int
    {
        $entry = FileObject::wrap($file);

        $seeders = $this->includeList($entry);

        $seeders = TypeCast::toArray($seeders);
        $seeders = array_reverse($seeders);

        $count = 0;

        foreach ($seeders as $seederFile) {
            $seeder = new Seeder(
                FileObject::wrap($seederFile),
                $this->db,
                $this->fakerService
            );
            $db     = $seeder->db;
            $orm    = $db->orm();
            $app    = $this->app;

            include $seederFile;

            if (is_callable($seeder->clear)) {
                $this->app->call($seeder->clear);
                $seeder->addEventDealer($this);

                $this->emitMessage(
                    "Clear seeder: <info>{$seeder->prettyName}</info> (<fg=gray>/{$seeder->file->getBasename()}</>)"
                );

                $count++;
            }
        }

        return $count;
    }

    protected function getSeedPrettyName(string $name): string
    {
        return ucwords(StrNormalize::toSpaceSeparated($name));
    }

    /**
     * includeList
     *
     * @param  FileObject  $entry
     *
     * @return  iterable
     */
    protected function includeList(FileObject $entry): iterable
    {
        $files = include $entry;

        if (!is_iterable($files)) {
            throw new \LogicException(
                sprintf(
                    'Seed entry file: %s should return array or iterable',
                    $entry->getPathname()
                )
            );
        }

        return $files;
    }

    /**
     * copyMigrationFile
     *
     * @param  string  $dir
     * @param  string  $name
     * @param  string  $source
     *
     * @return  FileCollection
     */
    public function copySeedFile(string $dir, string $name, string $source): FileCollection
    {
        $codeGenerator = $this->app->make(CodeGenerator::class);

        $date = new \DateTimeImmutable('now');

        $year    = $date->format('Y');

        return $codeGenerator->from($source)
            ->replaceTo(
                $dir,
                compact('name', 'year'),
            );
    }
}
