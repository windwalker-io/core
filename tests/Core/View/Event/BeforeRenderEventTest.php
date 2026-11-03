<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\State\AppState;
use Windwalker\Core\View\Event\BeforeRenderEvent;
use Windwalker\Core\View\View;
use Windwalker\DI\Container;

#[CoversClass(BeforeRenderEvent::class)]
class BeforeRenderEventTest extends TestCase
{
    #[Test]
    public function constructorAssignsResponseAndInheritedProperties(): void
    {
        $view = $this->createStub(View::class);
        $viewModel = new \stdClass();
        $state = new AppState(new Container());

        $event = new BeforeRenderEvent(
            response: ['content' => 'value'],
            view: $view,
            viewModel: $viewModel,
            state: $state,
            layout: 'default',
            data: ['x' => 1]
        );

        self::assertSame(['content' => 'value'], $event->response);
        self::assertSame($view, $event->view);
        self::assertSame($viewModel, $event->viewModel);
        self::assertSame($state, $event->state);
        self::assertSame('default', $event->layout);
        self::assertSame(['x' => 1], $event->data);
    }
}
