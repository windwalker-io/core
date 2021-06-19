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

/**
 * Migration UP: {% $version %}_{% $name %}.
 *
 * @var Migration          $mig
 * @var ConsoleApplication $app
 */
$mig->up(
    static function () use ($mig) {
        //
    }
);

/**
 * Migration DOWN.
 */
$mig->down(
    static function () use ($mig) {
        //
    }
);
