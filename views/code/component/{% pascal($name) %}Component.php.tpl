{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Closure;
use Windwalker\Core\Edge\Attribute\EdgeComponent;
use Windwalker\Edge\Component\AbstractComponent;

#[EdgeComponent('{% kebab($name) %}')]
class {% pascal($name) %}Component extends AbstractComponent
{
    public function render(): Closure|string
    {
        return fn () => '';
    }
}
