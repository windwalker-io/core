<?php

declare(strict_types=1);

namespace Windwalker\Core\Factory;

use Windwalker\Core\DI\ServiceFactoryInterface;
use Windwalker\Core\DI\ServiceFactoryTrait;
use Windwalker\DI\Attributes\Isolation;

#[Isolation]
class LoggerFactory implements ServiceFactoryInterface
{
    use ServiceFactoryTrait;

    public function getConfigPrefix(): string
    {
        return 'logs';
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultFactory(string $name, ...$args): mixed
    {
        return $this->config->getDeep($this->getFactoryPath($this->getDefaultName()));
    }
}
