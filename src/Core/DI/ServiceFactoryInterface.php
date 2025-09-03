<?php

declare(strict_types=1);

namespace Windwalker\Core\DI;

use Windwalker\Event\EventAwareInterface;

interface ServiceFactoryInterface extends EventAwareInterface
{
    public function has(?string $name = null): bool;

    public function create(?string $name = null, ...$args): object;

    public function get(?string $name = null, ...$args): object;
}
