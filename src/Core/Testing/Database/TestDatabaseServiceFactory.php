<?php

declare(strict_types=1);

namespace Windwalker\Core\Testing\Database;

use Windwalker\Core\Factory\DatabaseServiceFactory;

class TestDatabaseServiceFactory extends DatabaseServiceFactory
{
    protected \Closure $defaultFactory;

    public function getDefaultName(): ?string
    {
        return 'test';
    }

    public function setDefaultFactory(\Closure $defaultFactory): static
    {
        $this->defaultFactory = $defaultFactory;

        return $this;
    }

    protected function getDefaultFactory(string $name, ...$args): mixed
    {
        return $this->defaultFactory;
    }
}
