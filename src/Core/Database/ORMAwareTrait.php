<?php

declare(strict_types=1);

namespace Windwalker\Core\Database;

use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Attributes\Inject;
use Windwalker\ORM\ORM;

trait ORMAwareTrait
{
    #[Inject]
    protected ORM $orm;

    protected DatabaseAdapter $db {
        get => $this->orm->getDb();
    }
}
