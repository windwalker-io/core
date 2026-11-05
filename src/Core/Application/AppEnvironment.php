<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Windwalker\DI\Parameters;
use Windwalker\Core\Runtime\Runtime;

/**
 * A lightweight, injectable view of the app environment settings,
 * so services don't need to depend on the whole Application.
 */
class AppEnvironment implements AppEnvironmentInterface
{
    public function __construct(protected Parameters $config) {}

    public function isDebug(): bool
    {
        return (bool) $this->config->getDeep('app.debug');
    }

    public function getMode(): string
    {
        return (string) $this->config->getDeep('app.mode');
    }

    public function getName(): string
    {
        return (string) ($this->config->getDeep('app.name') ?? 'Windwalker');
    }

    public function getVerbosity(): AppVerbosity
    {
        $verbosity = $this->config->getDeep('app.verbosity');

        if ($verbosity === null || $verbosity === '') {
            return $this->isDebug() ? AppVerbosity::VERBOSE : AppVerbosity::NORMAL;
        }

        $verbosity = AppVerbosity::tryFrom((int) $verbosity) ?? AppVerbosity::NORMAL;

        if ($verbosity->value < AppVerbosity::VERBOSE->value && $this->isDebug()) {
            $verbosity = AppVerbosity::VERBOSE;
        }

        return $verbosity;
    }

    public function isVerbose(): bool
    {
        return $this->getVerbosity()->isVerbose();
    }

    public function isCli(): bool
    {
        return Runtime::isCli();
    }
}
