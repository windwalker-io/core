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
);

/**
 * Migration DOWN.
 */
$mig->down(
    static function () use ($mig) {
        $mig->dropTables('queue_failed_jobs');
    }
);
