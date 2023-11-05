<?php

namespace PHPSTORM_META {

    // Paths
    registerArgumentsSet(
        'sys_paths',
        '@root',
        '@bin',
        '@cache',
        '@etc',
        '@logs',
        '@resources',
        '@source',
        '@temp',
        '@views',
        '@vendor',
        '@public',
        '@migrations',
        '@seeders',
        '@languages',
    );

    expectedArguments(
        \Windwalker\Core\Application\ApplicationInterface::config(),
        0,
        argumentsSet('sys_paths')
    );

    expectedArguments(
        \Windwalker\Core\Application\ApplicationInterface::path(),
        0,
        argumentsSet('sys_paths')
    );

    expectedArguments(
        \Windwalker\Core\Runtime\Config::path(),
        0,
        argumentsSet('sys_paths')
    );

    expectedArguments(
        \Windwalker\Core\Application\PathResolver::path(),
        0,
        argumentsSet('sys_paths')
    );
}
