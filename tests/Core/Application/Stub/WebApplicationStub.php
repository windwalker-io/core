<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Windwalker\Core\Application\WebApplication;
use Windwalker\DI\Container;

/**
 * WebApplication subclass that exposes isCliRuntime() so tests can force
 * AppType::WEB (instead of the always-true CLI SAPI value), and that
 * overrides close() so the `instant` redirect path doesn't terminate
 * the PHPUnit process.
 */
class WebApplicationStub extends WebApplication
{
    public bool $closeCalled = false;

    public mixed $closeReturn = null;

    public function __construct(
        Container $container,
        private readonly bool $cliRuntime = true,
    ) {
        parent::__construct($container);
    }

    public bool $headersSentOverride = false;

    public function isCliRuntime(): bool
    {
        return $this->cliRuntime;
    }

    public function checkHeadersSent(): bool
    {
        return $this->headersSentOverride;
    }

    public function close(mixed $return = ''): void
    {
        $this->closeCalled = true;
        $this->closeReturn = $return;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
