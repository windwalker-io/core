<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Psr\Log\LoggerInterface;
use Windwalker\DI\Attributes\Isolation;

/**
 * The LoggerManager class.
 *
 * @method LoggerInterface create(?string $name = null, ...$args)
 * @method LoggerInterface get(?string $name = null, ...$args)
 *
 * @deprecated  Use container tags instead.
 */
#[Isolation]
class LoggerManager extends AbstractManager
{
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
