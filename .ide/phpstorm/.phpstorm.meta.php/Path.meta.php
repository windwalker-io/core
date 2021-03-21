<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

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
