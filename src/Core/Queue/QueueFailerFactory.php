<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use Windwalker\Core\DI\ServiceFactoryInterface;
use Windwalker\Core\DI\ServiceFactoryTrait;
use Windwalker\DI\Attributes\Isolation;

#[Isolation]
class QueueFailerFactory implements ServiceFactoryInterface
{
    use ServiceFactoryTrait;

    public function getConfigPrefix(): string
    {
        return 'queue';
    }

    public function getDefaultName(): ?string
    {
        return $this->config->getDeep('failer_default');
    }

    protected function getFactoryPath(string $name): string
    {
        return 'factories.failers.' . $name;
    }
}
