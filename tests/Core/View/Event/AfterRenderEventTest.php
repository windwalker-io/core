<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\View\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\State\AppState;
use Windwalker\Core\View\Event\AfterRenderEvent;
use Windwalker\Core\View\View;
use Windwalker\DI\Container;
use Windwalker\Http\Response\HtmlResponse;

#[CoversClass(AfterRenderEvent::class)]
class AfterRenderEventTest extends TestCase
{
    private function createEvent(): AfterRenderEvent
    {
        return new AfterRenderEvent(
            content: '<p>hello</p>',
            response: new HtmlResponse(),
            view: $this->createStub(View::class),
            viewModel: new \stdClass(),
            state: new AppState(new Container()),
            layout: 'default',
            data: ['x' => 1]
        );
    }

    #[Test]
    public function constructorAssignsContentAndResponseAndInheritedProperties(): void
    {
        $event = $this->createEvent();

        self::assertSame('<p>hello</p>', $event->content);
        self::assertSame('default', $event->layout);
        self::assertSame(['x' => 1], $event->data);
    }

    #[Test]
    public function addHeaderAddsHeaderToResponseAndReturnsSelfForChaining(): void
    {
        $event = $this->createEvent();

        $result = $event->addHeader('X-Test', 'one');

        self::assertSame($event, $result);
        self::assertSame(['one'], $event->response->getHeader('X-Test'));
    }

    #[Test]
    public function addHeaderAcceptsArrayOfValuesAndAddsEachAsSeparateHeaderLine(): void
    {
        $event = $this->createEvent();

        $event->addHeader('X-Test', ['one', 'two']);

        self::assertSame(['one', 'two'], $event->response->getHeader('X-Test'));
    }
}
