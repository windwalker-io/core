<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

interface AppEnvironmentInterface
{
    public function isDebug(): bool;

    public function getMode(): string;

    public function getName(): string;

    public function getVerbosity(): AppVerbosity;

    public function isVerbose(): bool;

    public function isCli(): bool;
}
