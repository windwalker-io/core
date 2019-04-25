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
 * @since  3.5.3
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
     * @since  3.5.3
     */
    public static function getEnvCmd(string $env = 'WINDWALKER_MODE', string $value = 'dev'): string
    {
        $prefix = Ioc::getEnvironment()->getPlatform()->isWin()
            ? 'set'
            : 'export';

        return sprintf('%s %s=%s', $prefix, $env, $value);
    }
}
