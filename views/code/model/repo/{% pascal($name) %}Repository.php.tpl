{% $phpOpen %}

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace App\Repository;

use Windwalker\Database\DatabaseAdapter;

/**
 * The {% pascal($name) %}Repository class.
 */
class {% pascal($name) %}Repository
{
    /**
     * {% pascal($name) %}Repository constructor.
     */
    public function __construct(protected DatabaseAdapter $db)
    {
        //
    }
}
