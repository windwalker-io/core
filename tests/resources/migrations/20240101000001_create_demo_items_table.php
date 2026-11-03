<?php

declare(strict_types=1);

use Windwalker\Core\Migration\AbstractMigration;
use Windwalker\Core\Migration\MigrateDown;
use Windwalker\Core\Migration\MigrateUp;
use Windwalker\Database\Schema\Schema;

/**
 * Representative migration fixture for
 * tests/Core/Migration/MigrationServiceRealDbTest.php.
 *
 * Exercises column types, length/precision, a unique key, and a composite
 * index that SQLite's lenient type affinity accepts regardless of
 * correctness, so this file is meant to run against a real MySQL/
 * PostgreSQL/SQL Server connection (see DatabaseTestTrait), not the
 * SQLite-backed tier 1 MigrationServiceTest.
 */
return new class () extends AbstractMigration {
    #[MigrateUp]
    public function up(): void
    {
        $this->createTable(
            'demo_items',
            function (Schema $schema) {
                $schema->primaryBigint('id');
                $schema->varchar('title');
                $schema->varchar('slug')->length(191);
                $schema->decimal('price')->length('20,6');
                $schema->integer('stock')->nullable(true);
                $schema->text('description')->nullable(true);
                $schema->json('meta')->nullable(true);
                $schema->timestamp('created')->defaultCurrent();
                $schema->timestamp('updated')->defaultCurrent()->onUpdateCurrent();

                $schema->addIndex(['stock', 'title']);
                $schema->addUniqueKey('slug');
            }
        );
    }

    #[MigrateDown]
    public function down(): void
    {
        $this->dropTables('demo_items');
    }
};
