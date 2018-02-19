<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Console;

/**
 * The CoreCommandTrait class.
 *
 * @since      3.0
 *
 * @deprecated Extend \Windwalker\Core\Console\CoreCommand instead.
 */
trait CoreCommandTrait
{
    public function bootCoreCommandTrait()
    {
        throw new \LogicException('Please do not use this trait, extend Windwalker\Core\Console\CoreCommand instead.');
    }
}
