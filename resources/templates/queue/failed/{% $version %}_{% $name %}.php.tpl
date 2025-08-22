{% $phpOpen %}

declare(strict_types=1);

namespace App\Migration;

use Windwalker\Database\Schema\Schema;
use Windwalker\Core\Migration\AbstractMigration;
use Windwalker\Core\Migration\MigrateUp;
use Windwalker\Core\Migration\MigrateDown;

return new /** {% $version %}_{% $name %} */ class extends AbstractMigration {
    #[MigrateUp]
    public function up(): void
    {
        $this->createTable(
            'queue_failed_jobs',
            function (Schema $schema) {
                $schema->primaryBigint('id');
                $schema->varchar('connection');
                $schema->varchar('channel');
                $schema->longtext('body');
                $schema->longtext('exception');
                $schema->datetime('created');
            }
        );
    }

    #[MigrateDown]
    public function down(): void
    {
        $this->dropTables('queue_failed_jobs');
    }
};
