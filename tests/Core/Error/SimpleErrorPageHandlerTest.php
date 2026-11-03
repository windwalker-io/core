<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Error;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Error\SimpleErrorPageHandler;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\DI\Container;
use Windwalker\Http\Output\OutputInterface;

#[CoversClass(SimpleErrorPageHandler::class)]
class SimpleErrorPageHandlerTest extends TestCase
{
    #[Test]
    public function invokeRendersAndRespondsThroughBoundOutput(): void
    {
        $exception = new Exception('Boom', 500);

        $renderer = $this->createMock(RendererService::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with(
                'windwalker.error.simple',
                self::callback(
                    static fn (array $data) => $data['exception'] === $exception
                )
            )
            ->willReturn('<html>Boom</html>');

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('service')
            ->with(RendererService::class)
            ->willReturn($renderer);

        $container = new Container();

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())
            ->method('respond')
            ->with(
                self::callback(
                    static function (ResponseInterface $response) {
                        return (string) $response->getBody() === '<html>Boom</html>'
                            && $response->getStatusCode() === 500;
                    }
                )
            );

        $container->share(OutputInterface::class, $output);

        $app->method('getContainer')->willReturn($container);

        $handler = new SimpleErrorPageHandler($app);

        $handler($exception);
    }

    #[Test]
    public function invokeFallsBackToStreamOutputWhenNoneIsBound(): void
    {
        $exception = new Exception('Boom', 404);

        $renderer = $this->createStub(RendererService::class);
        $renderer->method('render')->willReturn('<html>Not Found</html>');

        $app = $this->createStub(ApplicationInterface::class);
        $app->method('service')->willReturn($renderer);
        $app->method('getContainer')->willReturn(new Container());

        $handler = new SimpleErrorPageHandler($app);

        ob_start();
        $handler($exception);
        $output = ob_get_clean();

        self::assertSame('<html>Not Found</html>', $output);
    }

    #[Test]
    public function invokeEchoesAndStopsWhenRenderingThrows(): void
    {
        $exception = new Exception('Boom', 500);
        $renderException = new RuntimeException('Template broken');

        $renderer = $this->createStub(RendererService::class);
        $renderer->method('render')->willThrowException($renderException);

        $app = $this->createMock(ApplicationInterface::class);
        $app->method('service')->willReturn($renderer);
        $app->expects($this->never())->method('getContainer');

        $handler = new SimpleErrorPageHandler($app);

        $handler($exception);

        self::assertSame((string) $renderException, $this->getActualOutputForAssertion());
    }
}
