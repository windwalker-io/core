<?php

declare(strict_types=1);

namespace Windwalker\Core\Testing\Stub;

use Psr\Log\LogLevel;
use Stringable;
use Windwalker\Core\Application\AppClient;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\ApplicationTrait;
use Windwalker\DI\Container;

/**
 * Minimal ApplicationInterface implementer that exposes ApplicationTrait's
 * getType()/getClient()/isCliRuntime() combinations directly, without
 * booting a full Web/Micro/Console application.
 */
class ApplicationTraitStub implements ApplicationInterface
{
    use ApplicationTrait;

    public function __construct(
        private readonly AppClient $client = AppClient::WEB,
        private readonly bool $cliRuntime = true,
    ) {
        $this->container = new Container();
    }

    public function getClient(): AppClient
    {
        return $this->client;
    }

    public function isCliRuntime(): bool
    {
        return $this->cliRuntime;
    }

    public function addMessage(string|array $messages, ?string $type = 'info'): static
    {
        return $this;
    }

    public function log(string|Stringable $message, array $context = [], string $level = LogLevel::INFO): static
    {
        return $this;
    }

    public function close(mixed $return = ''): void
    {
        //
    }
}
