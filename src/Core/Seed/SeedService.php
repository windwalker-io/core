<?php

declare(strict_types=1);

namespace Windwalker\Core\Seed;

use DateTimeImmutable;
use SplFileInfo;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Core\Generator\CodeGenerator;
use Windwalker\Core\Generator\FileCollection;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Filesystem\FileObject;
use Windwalker\Utilities\Attributes\AttributesAccessor;
use Windwalker\Utilities\StrNormalize;
use Windwalker\Utilities\TypeCast;

use function Windwalker\fs;

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
     * @param  FakerService          $fakerService
     * @param  DatabaseAdapter|null  $db
     */
    public function __construct(
        protected ApplicationInterface $app,
        #[Autowire]
        protected FakerService $fakerService,
        protected ?DatabaseAdapter $db = null,
    ) {
    }

    public function import(string|SplFileInfo $file): int
    {
        $entry = FileObject::wrap($file);

        $seeder = new Seeder();
        $db = $this->db;
        $orm = $db->orm();
        $app = $this->app;

        $seeder = static::afterInclude($seeders = include $entry->getPathname(), $seeder)
            ->init($entry, $this->db, $this->fakerService);

        if (!is_array($seeders) && $seeder->getImportClosure()) {
            $this->runImport($seeder);

            return 1;
        }

        $count = 0;

        foreach ($seeders as $seederFile) {
            $seeder = new Seeder();
            $db = $this->db;
            $orm = $db->orm();
            $app = $this->app;

            $seeder = static::afterInclude(include $seederFile, $seeder)
                ->init(fs($seederFile), $this->db, $this->fakerService);

            if ($seeder->getImportClosure()) {
                $this->runImport($seeder);
                $count++;
            }
        }

        return $count;
    }

    public function clear(string|SplFileInfo $file): int
    {
        $entry = FileObject::wrap($file);

        $seeder = new Seeder();
        $db = $this->db;
        $orm = $db->orm();
        $app = $this->app;

        $seeder = static::afterInclude($seeders = include $entry, $seeder)
            ->init($entry, $this->db, $this->fakerService);

        if (!is_array($seeders) && $seeder->getClearClosure()) {
            $this->runClear($seeder);

            return 1;
        }

        $seeders = TypeCast::toArray($seeders);
        $seeders = array_reverse($seeders);

        $count = 0;

        foreach ($seeders as $seederFile) {
            $seeder = new Seeder();
            $db = $this->db;
            $orm = $db->orm();
            $app = $this->app;

            $seeder = static::afterInclude(include $seederFile, $seeder)
                ->init(fs($seederFile), $this->db, $this->fakerService);

            if ($seeder->getClearClosure()) {
                $this->runClear($seeder);

                $count++;
            }
        }

        return $count;
    }

    protected function getSeedPrettyName(string $name): string
    {
        return ucwords(StrNormalize::toSpaceSeparated($name));
    }

    protected static function afterInclude(mixed $included, Seeder $seeder): SeederTask
    {
        if (!$included instanceof SeederTask) {
            return $seeder;
        }

        return $included;
    }

    // /**
    //  * includeList
    //  *
    //  * @param  FileObject  $entry
    //  *
    //  * @return  ?iterable
    //  */
    // protected function includeSeeders(FileObject $entry): ?iterable
    // {
    //     $seeder = include $entry;
    //
    //     if (!is_iterable($seeder)) {
    //         throw new \LogicException(
    //             sprintf(
    //                 'Seed entry file: %s should return array ,iterable or void',
    //                 $entry->getPathname()
    //             )
    //         );
    //     }
    //
    //     return $seeder;
    // }

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

        $date = new DateTimeImmutable('now');

        $year = $date->format('Y');

        return $codeGenerator->from($source)
            ->replaceTo(
                $dir,
                compact('name', 'year'),
            );
    }

    protected function runImport(SeederTask $seeder): void
    {
        $seeder->addEventDealer($this);

        $this->emitMessage(
            "Import seeder: <info>{$seeder->prettyName}</info> (<fg=gray>/{$seeder->file->getBasename()}</>)"
        );

        $this->app->call($seeder->getImportClosure());

        if ($seeder->count > 0) {
            $this->emitMessage('');
        }

        $this->emitMessage('  <comment>Import completed...</comment>');

        return;
    }

    protected function runClear(SeederTask $seeder): void
    {
        $seeder->addEventDealer($this);

        $this->app->call($seeder->getClearClosure());

        $this->emitMessage(
            "Clear seeder: <info>{$seeder->prettyName}</info> (<fg=gray>/{$seeder->file->getBasename()}</>)"
        );
    }
}
