{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Data\RecordInterface;
use Windwalker\Data\RecordTrait;

class {% pascal($name) %} implements RecordInterface
{
    use RecordTrait;

    public function __construct(
        //
    ) {
    }
}
