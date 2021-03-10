<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Migration\Command;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\DI\Attributes\Inject;

/**
 * Trait MigrationTrait
 */
trait MigrationTrait
{
    #[Inject]
    protected ApplicationInterface $app;

    public function getMigrationFolder(): string
    {
        return $this->app->config('@resources') . '/migrations';
    }
}
