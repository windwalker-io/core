<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

interface SecretKeeperInterface
{
    public function getSecret(?string $deriveFor = null): string;
}
