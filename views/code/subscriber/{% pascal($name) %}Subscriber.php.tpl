{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Event\Attributes\ListenTo;

#[EventSubscriber]
class {% pascal($name) %}Subscriber
{
    #[ListenTo(FooEvent::class)]
    public function foo($event): void
    {
        //
    }
}
