<?php

/**
 * Part of amiko project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use Windwalker\Core\Manager\AbstractManager;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\Queue\Failer\QueueFailerInterface;

/**
 * The QueueFailerManager class.
 *
 * @method QueueFailerInterface get(?string $name = null, ...$args)
 */
#[Isolation]
class QueueFailerManager extends AbstractManager
{
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
