<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Migration;

use Windwalker\Core\Ioc;

/**
 * The MigrationHelper class.
 *
 * @since  __DEPLOY_VERSION__
 */
class MigrationHelper
{
    /**
     * getEnvCmd
     *
     * @param string $env
     * @param string $value
     *
     * @return  string
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function getEnvCmd(string $env = 'WINDWALKER_MODE', string $value = 'dev'): string
    {
        $prefix = Ioc::getEnvironment()->getPlatform()->isWin()
            ? 'setenv'
            : 'export';

        return sprintf('%s %s=%s', $prefix, $env, $value);
    }
}
