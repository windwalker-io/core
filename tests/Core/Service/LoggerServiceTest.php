<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Service;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Windwalker\Core\Manager\LoggerManager;
use Windwalker\Core\Service\LoggerService;

#[CoversClass(LoggerService::class)]
class LoggerServiceTest extends TestCase
{
    private function createService(?LoggerManager $manager = null): LoggerService
    {
        return new LoggerService($manager ?? $this->createStub(LoggerManager::class));
    }

    #[Test]
    public function getLoggerDelegatesToManager(): void
    {
        $logger = $this->createStub(LoggerInterface::class);

        $manager = $this->createMock(LoggerManager::class);
        $manager->expects($this->once())
            ->method('get')
            ->with('app')
            ->willReturn($logger);

        $service = $this->createService($manager);

        self::assertSame($logger, $service->getLogger('app'));
    }

    #[Test]
    public function logWritesMessageToChannelLoggerWithGivenLevel(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with(LogLevel::NOTICE, 'Boom', ['foo' => 'bar']);

        $manager = $this->createStub(LoggerManager::class);
        $manager->method('get')->willReturnCallback(static fn(?string $name = null) => $logger);

        $service = $this->createService($manager);

        self::assertSame($service, $service->log('app', LogLevel::NOTICE, 'Boom', ['foo' => 'bar']));
    }

    #[Test]
    public function logRecursesForEachChannelWhenChannelIsArray(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('log')
            ->with(LogLevel::INFO, 'Boom', []);

        $manager = $this->createStub(LoggerManager::class);
        $manager->method('get')
            ->willReturnCallback(static fn(?string $name = null) => $logger);

        $service = $this->createService($manager);

        $service->log(['app', 'sys'], LogLevel::INFO, 'Boom');
    }

    #[Test]
    public function logRecursesForEachMessageWhenMessageIsArray(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('log')
            ->with(LogLevel::INFO, self::logicalOr('First', 'Second'), []);

        $manager = $this->createStub(LoggerManager::class);
        $manager->method('get')->willReturnCallback(static fn(?string $name = null) => $logger);

        $service = $this->createService($manager);

        $service->log('app', LogLevel::INFO, ['First', 'Second']);
    }

    #[Test]
    public function logConvertsThrowableMessageIntoExceptionContext(): void
    {
        $exception = new Exception('Boom');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with(LogLevel::ERROR, 'Boom', ['exception' => $exception]);

        $manager = $this->createStub(LoggerManager::class);
        $manager->method('get')->willReturnCallback(static fn(?string $name = null) => $logger);

        $service = $this->createService($manager);

        $service->log('app', LogLevel::ERROR, $exception);
    }

    #[Test]
    public function logIgnoresGivenContextWhenMessageIsThrowable(): void
    {
        $exception = new Exception('Boom');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with(LogLevel::ERROR, 'Boom', ['exception' => $exception]);

        $manager = $this->createStub(LoggerManager::class);
        $manager->method('get')->willReturnCallback(static fn(?string $name = null) => $logger);

        $service = $this->createService($manager);

        $service->log('app', LogLevel::ERROR, $exception, ['foo' => 'bar']);
    }

    /**
     * @return  array<string, array{0: string, 1: string}>
     */
    public static function levelMethodProvider(): array
    {
        return [
            'emergency' => ['emergency', LogLevel::EMERGENCY],
            'alert' => ['alert', LogLevel::ALERT],
            'critical' => ['critical', LogLevel::CRITICAL],
            'error' => ['error', LogLevel::ERROR],
            'warning' => ['warning', LogLevel::WARNING],
            'notice' => ['notice', LogLevel::NOTICE],
            'info' => ['info', LogLevel::INFO],
            'debug' => ['debug', LogLevel::DEBUG],
        ];
    }

    #[Test]
    #[DataProvider('levelMethodProvider')]
    public function levelMethodLogsWithExpectedLevel(string $method, string $level): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with($level, 'Boom', ['foo' => 'bar']);

        $manager = $this->createStub(LoggerManager::class);
        $manager->method('get')->willReturnCallback(static fn(?string $name = null) => $logger);

        $service = $this->createService($manager);

        self::assertSame($service, $service->{$method}('app', 'Boom', ['foo' => 'bar']));
    }

    #[Test]
    public function parseTraceReplacesRootPathWithPlaceholder(): void
    {
        $trace = WINDWALKER_ROOT . '/src/Foo.php:10';

        self::assertSame('   {ROOT}/src/Foo.php:10', LoggerService::parseTrace($trace));
    }
}
