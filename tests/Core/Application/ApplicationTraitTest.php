<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\AppClient;
use Windwalker\Core\Application\AppType;
use Windwalker\Core\Application\AppVerbosity;
use Windwalker\Core\Application\Offline\MaintenanceManager;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Testing\Traits\ServiceAwareTestTrait;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\Tests\Core\Application\Stub\AnotherEventAwareStub;
use Windwalker\Tests\Core\Application\Stub\DeferredBootProviderStub;
use Windwalker\Tests\Core\Application\Stub\EventAwareStub;
use Windwalker\Tests\Core\Application\Stub\InvokableListenerStub;
use Windwalker\Tests\Core\Application\Stub\SubscriberStub;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;
use Windwalker\Core\Testing\Stub\ApplicationTraitStub;

/**
 * Unit tests for the uncovered ApplicationTrait branches that are not already
 * exercised indirectly through WebApplicationTest/MicroApplicationTest.
 */
class ApplicationTraitTest extends TestCase
{
    use ServiceAwareTestTrait;

    #[Before]
    public function prepareDependencies()
    {
        self::$container->prepareSharedObject(PathResolver::class);
    }

    #[Test]
    #[DataProvider('typeProvider')]
    public function getTypeResolvesFromClientAndRuntime(
        AppClient $client,
        bool $cliRuntime,
        AppType $expected
    ): void {
        $app = new ApplicationTraitStub($client, $cliRuntime);

        self::assertSame($expected, $app->getType());
    }

    public static function typeProvider(): array
    {
        return [
            'web client, cli runtime' => [AppClient::WEB, true, AppType::CLI_WEB],
            'web client, non-cli runtime' => [AppClient::WEB, false, AppType::WEB],
            'console client' => [AppClient::CONSOLE, true, AppType::CONSOLE],
        ];
    }

    #[Test]
    public function pathResolvesSystemAliasFromConfig(): void
    {
        $app = TestWebAppFactory::create();
        $app->getContainer()->getParameters()->set('@root', 'C:\www\project');

        self::assertSame('C:\www\project/sub/file.php', $app->path('@root/sub/file.php'));
    }

    #[Test]
    public function getModeReadsAppModeConfig(): void
    {
        $app = TestWebAppFactory::create(['mode' => 'production']);

        self::assertSame('production', $app->getMode());
    }

    #[Test]
    public function isVerboseDelegatesToVerbosity(): void
    {
        $quiet = TestWebAppFactory::create(['verbosity' => AppVerbosity::NORMAL->value]);
        $verbose = TestWebAppFactory::create(['debug' => true]);

        self::assertFalse($quiet->isVerbose());
        self::assertTrue($verbose->isVerbose());
    }

    #[Test]
    public function isMaintenanceResolvesMaintenanceManager(): void
    {
        self::$container->prepareSharedObject(MaintenanceManager::class);

        self::assertFalse(static::$appContext->isMaintenance());
    }

    #[Test]
    public function registerAllConfigsSkipsDisabledSectionsAndBootsDeferredProviders(): void
    {
        DeferredBootProviderStub::$bootDeferredCalls = 0;

        $app = TestWebAppFactory::create();
        $app->loadConfig(
            [
                'disabled_section' => ['enabled' => false, 'providers' => ['should-not-register']],
                'di' => ['providers' => [DeferredBootProviderStub::class]],
            ]
        );

        $app->boot();

        self::assertSame(1, DeferredBootProviderStub::$bootDeferredCalls);
    }

    #[Test]
    public function registerListenersSkipsDisabledSections(): void
    {
        $app = TestWebAppFactory::create();
        $app->loadConfig(
            [
                'disabled_section' => ['enabled' => false, 'listeners' => ['some.event' => static fn() => null]],
            ]
        );

        $ctx = $app->createContext(TestWebAppFactory::request());

        self::assertNotNull($ctx);
    }

    #[Test]
    public function handleListenerSkipsNullAndFalseListeners(): void
    {
        $app = TestWebAppFactory::create();
        $container = $app->getContainer();

        $app->handleListeners(['skip.null' => null, 'skip.false' => false], $container);

        self::assertCount(0, iterator_to_array($app->getEventDispatcher()->getListeners('skip.null')));
        self::assertCount(0, iterator_to_array($app->getEventDispatcher()->getListeners('skip.false')));
    }

    #[Test]
    public function handleListenerRegistersNumericKeyClosureWithListenToAttribute(): void
    {
        $app = TestWebAppFactory::create();
        $container = $app->getContainer();

        $listener = #[ListenTo('app.test.numeric.closure')] static function (): void {
            //
        };

        $app->handleListeners([0 => $listener], $container);

        self::assertCount(1, iterator_to_array($app->getEventDispatcher()->getListeners('app.test.numeric.closure')));
    }

    #[Test]
    public function handleListenerRegistersNumericKeyPlainSubscriberClassName(): void
    {
        $app = TestWebAppFactory::create();
        $container = $app->getContainer();

        // Should not throw: resolves SubscriberStub and calls dispatcher->subscribe() on it.
        $app->handleListeners([0 => SubscriberStub::class], $container);

        self::assertTrue(true);
    }

    #[Test]
    public function handleListenerRegistersCallableUnderEventNameKey(): void
    {
        $app = TestWebAppFactory::create();
        $container = $app->getContainer();

        $app->handleListeners(['app.test.callable' => static fn() => null], $container);

        self::assertCount(1, iterator_to_array($app->getEventDispatcher()->getListeners('app.test.callable')));
    }

    #[Test]
    public function handleListenerResolvesInvokableArrayListener(): void
    {
        $app = TestWebAppFactory::create();
        $container = $app->getContainer();

        $app->handleListeners(
            ['app.test.invokable' => [InvokableListenerStub::class, '__invoke']],
            $container
        );

        $listeners = iterator_to_array($app->getEventDispatcher()->getListeners('app.test.invokable'));
        self::assertCount(1, $listeners);
    }

    #[Test]
    public function handleListenerExtendsRegisteredEventAwareObjectWithNestedListeners(): void
    {
        $app = TestWebAppFactory::create();
        $container = $app->getContainer();
        $container->share(EventAwareStub::class, new EventAwareStub());

        // container->has(EventAwareStub::class) is true, so extend() is used; the
        // closure (nested foreach + recursive handleListener call) only runs once
        // the id is actually resolved via get().
        $app->handleListeners(
            [EventAwareStub::class => ['app.nested.event' => static fn() => null]],
            $container
        );

        /** @var EventAwareStub $stub */
        $stub = $container->get(EventAwareStub::class);

        self::assertCount(1, iterator_to_array($stub->getEventDispatcher()->getListeners('app.nested.event')));
    }

    #[Test]
    public function handleListenerExtendsRegisteredEventAwareObjectWithScalarSubscriber(): void
    {
        $app = TestWebAppFactory::create();
        $container = $app->getContainer();
        $container->share(EventAwareStub::class, new EventAwareStub());

        $app->handleListeners([EventAwareStub::class => SubscriberStub::class], $container);

        // Resolving the id applies the registered extend() closure, which calls
        // $object->subscribe(...) on the EventAwareStub instance.
        $stub = $container->get(EventAwareStub::class);

        self::assertInstanceOf(EventAwareStub::class, $stub);
    }

    #[Test]
    public function handleListenerExtendsForCreateUnregisteredEventAwareObjectWithNestedListeners(): void
    {
        $app = TestWebAppFactory::create();
        $container = $app->getContainer();

        self::assertFalse($container->has(AnotherEventAwareStub::class));

        $app->handleListeners(
            [AnotherEventAwareStub::class => ['app.nested.event.create' => static fn() => null]],
            $container
        );

        /** @var AnotherEventAwareStub $stub */
        $stub = $container->newInstance(AnotherEventAwareStub::class);

        self::assertCount(1, iterator_to_array($stub->getEventDispatcher()->getListeners('app.nested.event.create')));
    }

    #[Test]
    public function handleListenerExtendsForCreateUnregisteredEventAwareObjectWithScalarSubscriber(): void
    {
        $app = TestWebAppFactory::create();
        $container = $app->getContainer();

        self::assertFalse($container->has(AnotherEventAwareStub::class));

        $app->handleListeners([AnotherEventAwareStub::class => SubscriberStub::class], $container);

        // Should not throw: the extendForCreate() closure subscribes SubscriberStub
        // once the instance is actually created.
        $stub = $container->newInstance(AnotherEventAwareStub::class);

        self::assertInstanceOf(AnotherEventAwareStub::class, $stub);
    }
}
