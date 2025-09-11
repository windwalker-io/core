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
        // $this->createTable(
        //     {% $entity %}::class,
        //     function (Schema $schema) {}
        // );
    }

    #[MigrateDown]
    public function down(): void
    {
        // $this->dropTables({% $entity %}::class);
    }
};
