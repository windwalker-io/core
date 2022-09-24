<?php

/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

return [
    'debugger' => [
        'enabled' => env('APP_ENV') === 'dev',

        'editor' => env('DEBUGGER_EDITOR', 'phpstorm'),

        'listeners' => [
            \Windwalker\Core\Application\AppContext::class => [
                \Windwalker\Debugger\Subscriber\DebuggerSubscriber::class
            ]
        ],

        'providers' => [
            \Windwalker\Debugger\DebuggerPackage::class
        ],

        'cache' => [
            'max_files' => 100
        ]
    ]
];
