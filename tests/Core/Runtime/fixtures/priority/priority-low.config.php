<?php

declare(strict_types=1);

use Windwalker\Core\Attributes\ConfigModule;
use Windwalker\Utilities\Iterator\PriorityQueue;

return
    #[ConfigModule(priority: PriorityQueue::LOW)]
    function (ConfigModule $module) {
        return ['shared' => 'from-low', 'only-low' => 'low-val'];
    };
