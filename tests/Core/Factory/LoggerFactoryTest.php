<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Factory;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Factory\LoggerFactory;
use Windwalker\Core\Manager\LoggerManager;
use Windwalker\Core\Service\LoggerService;
use Windwalker\DI\Container;

#[CoversClass(LoggerFactory::class)]
class LoggerFactoryTest extends TestCase
{
    private LoggerService $service;

    private TestHandler $handler;

    private Logger $logger;

    protected function setUp(): void
    {
        $container = new Container();
        $container->setParameters(['logs' => ['global_handlers' => [], 'global_processors' => []]]);

        $this->service = new LoggerService($this->createStub(LoggerManager::class));
        $container->share(LoggerService::class, $this->service);

        $this->handler = new TestHandler();

        $this->logger = LoggerFactory::monolog('test')($container);
        $this->logger->pushHandler($this->handler);
    }

    #[Test]
    public function channelLoggerMergesGlobalContext(): void
    {
        $this->service->setGlobalContext(['request_id' => 'abc', 'foo' => 'global']);

        $this->logger->info('Hello', ['foo' => 'user', 'bar' => 'baz']);

        self::assertSame(
            ['request_id' => 'abc', 'foo' => 'user', 'bar' => 'baz'],
            $this->handler->getRecords()[0]->context
        );
    }

    #[Test]
    public function channelLoggerStopsMergingAfterReset(): void
    {
        $this->service->setGlobalContext(['request_id' => 'abc']);
        $this->service->resetGlobalContext();

        $this->logger->info('Hello', ['bar' => 'baz']);

        self::assertSame(['bar' => 'baz'], $this->handler->getRecords()[0]->context);
    }
}
