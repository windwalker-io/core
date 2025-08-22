{% $phpOpen %}

declare(strict_types=1);

namespace App\Migration;

use Windwalker\Core\Migration\AbstractMigration;
use Windwalker\Core\Migration\MigrateUp;
use Windwalker\Core\Migration\MigrateDown;
use Windwalker\Database\Schema\Schema;

return new /** {% $version %}_{% $name %} */ class extends AbstractMigration {
    #[MigrateUp]
    public function up(): void
    {
        $this->createTable(
            'queue_jobs',
            function (Schema $schema) {
                $schema->primaryBigint('id');
                $schema->varchar('channel');
                $schema->longtext('body');
                $schema->tinyint('attempts');
                $schema->datetime('created')->nullable(true);
                $schema->datetime('visibility')->nullable(true);
                $schema->datetime('reserved')->nullable(true);

                $schema->addIndex('channel');
            }
        );
    }

    #[MigrateDown]
    public function down(): void
    {
        $this->dropTables('queue_jobs');
    }
};
