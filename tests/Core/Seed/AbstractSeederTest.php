<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Seed;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Windwalker\Core\Seed\AbstractSeeder;
use Windwalker\Core\Seed\FakerService;
use Windwalker\Core\Seed\SeedClear;
use Windwalker\Core\Seed\SeedImport;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Tests\Testing\Traits\InMemoryDatabaseTestTrait;

#[CoversClass(AbstractSeeder::class)]
class AbstractSeederTest extends TestCase
{
    use InMemoryDatabaseTestTrait;

    protected function prepareInMemoryDatabaseSchema(DatabaseAdapter $db): void
    {
        $db->execute(
            <<<'SQL'
            CREATE TABLE foo (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL
            )
            SQL
        );

        $db->getWriter()->insertOne('foo', ['name' => 'a'], 'id');
        $db->getWriter()->insertOne('foo', ['name' => 'b'], 'id');
    }

    private function createSeeder(): AbstractSeeder
    {
        return new class () extends AbstractSeeder {
            #[SeedImport]
            public function import(): void {}

            #[SeedClear]
            public function clear(): void {}
        };
    }

    #[Test]
    public function initSetsNameAndPrettyNameFromFile(): void
    {
        $seeder = $this->createSeeder();

        $file = new SplFileInfo('/seeders/create_user_data.php');

        $seeder->init($file, $this->db, new FakerService());

        self::assertSame('create_user_data', $seeder->name);
        self::assertSame('Create User Data', $seeder->prettyName);
        self::assertSame($file, $seeder->file);
        self::assertSame($this->db, $seeder->db);
    }

    #[Test]
    public function truncateEmptiesGivenTables(): void
    {
        $seeder = $this->createSeeder();
        $seeder->init(new SplFileInfo('main.php'), $this->db, new FakerService());

        self::assertSame(2, (int) $this->db->prepare('SELECT COUNT(*) FROM foo')->result());

        $seeder->truncate('foo');

        self::assertSame(0, (int) $this->db->prepare('SELECT COUNT(*) FROM foo')->result());
    }

    #[Test]
    public function getImportHandlerReturnsClosureForMethodWithSeedImportAttribute(): void
    {
        $seeder = $this->createSeeder();
        $seeder->init(new SplFileInfo('main.php'), $this->db, new FakerService());

        $handler = $seeder->getImportHandler();

        self::assertInstanceOf(Closure::class, $handler);
    }

    #[Test]
    public function getClearHandlerReturnsClosureForMethodWithSeedClearAttribute(): void
    {
        $seeder = $this->createSeeder();
        $seeder->init(new SplFileInfo('main.php'), $this->db, new FakerService());

        $handler = $seeder->getClearHandler();

        self::assertInstanceOf(Closure::class, $handler);
    }

    #[Test]
    public function getImportHandlerReturnsNullWhenNoMethodHasTheAttribute(): void
    {
        $seeder = new class () extends AbstractSeeder {};
        $seeder->init(new SplFileInfo('main.php'), $this->db, new FakerService());

        self::assertNull($seeder->getImportHandler());
    }

    #[Test]
    public function getClearHandlerReturnsNullWhenNoMethodHasTheAttribute(): void
    {
        $seeder = new class () extends AbstractSeeder {};
        $seeder->init(new SplFileInfo('main.php'), $this->db, new FakerService());

        self::assertNull($seeder->getClearHandler());
    }
}
