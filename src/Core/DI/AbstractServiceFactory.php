<?php

declare(strict_types=1);

namespace Windwalker\Core\DI;

use Windwalker\Core\Runtime\Config;

abstract class AbstractServiceFactory
{
    public function __construct(protected Config $config)
    {
    }

    public function getConfig(): Config
    {
        return $this->getServiceConfig($this->config);
    }

    abstract public function getServiceConfig(Config $config);
}
