<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2018 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Seeder;

use Windwalker\Console\Command\Command;
use Windwalker\Environment\PlatformHelper;

/**
 * The CountingOutputTrait class.
 *
 * @since  3.4.6
 */
trait CountingOutputTrait
{
    /**
     * Property count.
     *
     * @var  int
     */
    protected $count = 0;

    /**
     * outCounting
     *
     * @return  Command
     */
    public function outCounting()
    {
        // @see  https://gist.github.com/asika32764/19956edcc5e893b2cbe3768e91590cf1
        if (PlatformHelper::isWindows()) {
            $loading = ['|', '/', '-', '\\'];
        } else {
            $loading = ['◐', '◓', '◑', '◒'];
        }

        $this->count++;

        $icon = $loading[$this->count % count($loading)];

        $this->command->out("\r  ({$this->count}) $icon ", false);

        return $this->command;
    }

    /**
     * resetCount
     *
     * @return  $this
     *
     * @since  3.4.6
     */
    public function resetCount()
    {
        $this->count = 0;

        return $this;
    }
}
