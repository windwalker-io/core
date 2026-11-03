<?php

declare(strict_types=1);

use Windwalker\Core\Attributes\ConfigModule;

return
    #[ConfigModule(name: 'closure-env-mismatch', env: 'staging')]
    function (ConfigModule $module) {
        return ['from' => 'closure-env-mismatch'];
    };
