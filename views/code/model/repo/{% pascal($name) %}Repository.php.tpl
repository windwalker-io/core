{% $phpOpen %}

declare(strict_types=1);

namespace App\Repository;

use Windwalker\Database\DatabaseAdapter;

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
