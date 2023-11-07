{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Closure;
use Windwalker\Edge\Component\AbstractComponent;

class {% pascal($name) %}Component extends AbstractComponent
{
    public function render(): Closure|string
    {
        return fn () => '';
    }
}
