<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View\Stub;

use Windwalker\Event\Attributes\EventSubscriber;

/**
 * A ViewModelStub variant marked with #[EventSubscriber], used by ViewTest
 * to exercise View::render()'s EventSubscriber::isSubscriber() branch that
 * registers the view model as an event subscriber.
 */
#[EventSubscriber]
class SubscriberViewModelStub extends ViewModelStub {}
