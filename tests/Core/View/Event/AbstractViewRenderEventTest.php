<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\State\AppState;
use Windwalker\Core\View\Event\PrepareDataEvent;
use Windwalker\Core\View\View;
use Windwalker\DI\Container;

/**
 * Covers the shared behavior in AbstractViewRenderEvent through its concrete
 * PrepareDataEvent subclass, since the abstract class adds no behavior of
 * its own beyond the constructor and the two deprecated by-ref accessors.
 */
#[CoversClass(PrepareDataEvent::class)]
class AbstractViewRenderEventTest extends TestCase
{
    private function createEvent(array $data = ['a' => 1]): PrepareDataEvent
    {
        $view = $this->createStub(View::class);
        $viewModel = new \stdClass();
        $state = new AppState(new Container());

        return new PrepareDataEvent(
            view: $view,
            viewModel: $viewModel,
            state: $state,
            layout: 'default',
            data: $data
        );
    }

    #[Test]
    public function constructorAssignsAllConstructorPromotedProperties(): void
    {
        $view = $this->createStub(View::class);
        $viewModel = new \stdClass();
        $state = new AppState(new Container());

        $event = new PrepareDataEvent(
            view: $view,
            viewModel: $viewModel,
            state: $state,
            layout: 'site.default',
            data: ['foo' => 'bar']
        );

        self::assertSame($view, $event->view);
        self::assertSame($viewModel, $event->viewModel);
        self::assertSame($state, $event->state);
        self::assertSame('site.default', $event->layout);
        self::assertSame(['foo' => 'bar'], $event->data);
    }

    #[Test]
    public function getDataReturnsReferenceToDataProperty(): void
    {
        $event = $this->createEvent(['a' => 1]);

        $ref = &$event->getData();
        $ref['b'] = 2;

        self::assertSame(['a' => 1, 'b' => 2], $event->data);
    }

    #[Test]
    public function getLayoutReturnsReferenceToLayoutProperty(): void
    {
        $event = $this->createEvent();

        $ref = &$event->getLayout();
        $ref = 'changed.layout';

        self::assertSame('changed.layout', $event->layout);
    }
}
