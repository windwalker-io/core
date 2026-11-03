<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Controller;

use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Controller\DelegatingController;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;
use Windwalker\Tests\Core\Application\Stub\ViewStub;
use Windwalker\Tests\Core\Controller\Stub\DelegatingTargetControllerStub;

/**
 * Light integration tests for DelegatingController, exercised through a real
 * AppContext (same tier-2 recipe as
 * tests/Core/Application/AppContextTest.php) since it calls `$this->app->call()`.
 */
class DelegatingControllerTest extends TestCase
{
    private function createContext(): AppContext
    {
        return TestWebAppFactory::create()->createContext(TestWebAppFactory::request());
    }

    private function createDelegating(?object $controller = null): DelegatingController
    {
        return new DelegatingController($this->createContext(), $controller ?? new DelegatingTargetControllerStub());
    }

    #[Test]
    public function executeCallsExistingMethodOnController(): void
    {
        $delegating = $this->createDelegating();

        self::assertSame('save:7', $delegating->execute('save', ['id' => '7']));
    }

    #[Test]
    public function executeMapsViewArgumentThroughViewMap(): void
    {
        $delegating = $this->createDelegating();
        $delegating->setViewMap(['list' => 'App\\View\\ListView']);

        $result = $delegating->execute('show', ['view' => 'list']);

        self::assertSame('App\\View\\ListView', $result);
        self::assertSame(['list' => 'App\\View\\ListView'], $delegating->getViewMap());
    }

    #[Test]
    public function executeLeavesUnmappedViewArgumentUnchanged(): void
    {
        $delegating = $this->createDelegating();
        $delegating->setViewMap(['list' => 'App\\View\\ListView']);

        self::assertSame('detail', $delegating->execute('show', ['view' => 'detail']));
    }

    #[Test]
    public function executeFallsBackToRenderViewForIndexTaskWhenMethodMissing(): void
    {
        $delegating = $this->createDelegating();

        $response = $delegating->execute('index', ['view' => ViewStub::class]);

        $body = json_decode((string) $response->getBody(), true);

        self::assertSame(['default' => true, 'is_child' => false], $body['options']);
        self::assertSame([], $body['data']);
    }

    #[Test]
    public function executeThrowsLogicExceptionWhenTaskMissingAndNotIndex(): void
    {
        $delegating = $this->createDelegating();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains(
            sprintf('Method: %s::missingTask() not found.', DelegatingTargetControllerStub::class)
        );

        $delegating->execute('missingTask');
    }

    #[Test]
    public function invokeDelegatesToExecute(): void
    {
        $delegating = $this->createDelegating();

        self::assertSame('save:8', ($delegating)('save', ['id' => '8']));
    }

    #[Test]
    public function magicCallDelegatesToExecute(): void
    {
        $delegating = $this->createDelegating();

        self::assertSame('save:9', $delegating->__call('save', ['id' => '9']));
    }
}
