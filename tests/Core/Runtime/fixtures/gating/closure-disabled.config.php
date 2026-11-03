<?php

declare(strict_types=1);

use Windwalker\Core\Attributes\ConfigModule;

return
    #[ConfigModule(name: 'closure-disabled', enabled: false)]
    function (ConfigModule $module) {
        return ['from' => 'closure-disabled'];
    };
