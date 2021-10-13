{% $phpOpen %}

/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) {% $year %}.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace App\Migration;

use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Migration\Migration;
use Windwalker\Database\Schema\Schema;

/**
 * Migration UP: {% $version %}_{% $name %}.
 *
 * @var Migration          $mig
 * @var ConsoleApplication $app
 */
$mig->up(
    static function () use ($mig) {
        $mig->createTable(
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
);

/**
 * Migration DOWN.
 */
$mig->down(
    static function () use ($mig) {
        $mig->dropTables('queue_jobs');
    }
);
