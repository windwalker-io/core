<?php

declare(strict_types=1);

use Windwalker\Core\Attributes\ConfigModule;

return
    #[ConfigModule(name: 'closure-named', priority: 50)]
    function (ConfigModule $module) {
        return ['from' => 'closure-named'];
    };
