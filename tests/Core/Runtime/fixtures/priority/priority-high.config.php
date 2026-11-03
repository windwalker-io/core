<?php

declare(strict_types=1);

use Windwalker\Core\Attributes\ConfigModule;
use Windwalker\Utilities\Iterator\PriorityQueue;

return
    #[ConfigModule(priority: PriorityQueue::HIGH)]
    function (ConfigModule $module) {
        return ['shared' => 'from-high', 'only-high' => 'high-val'];
    };
