<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Console;

use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Console\Command\Command;
use Windwalker\Ioc;

/**
 * The CoreCommand class.
 *
 * @since  3.0
 */
class CoreCommand extends Command
{
    /**
     * Property console.
     *
     * @var  CoreConsole
     */
    protected $console;

    /**
     * Add an argument(sub command) setting. This method in Command use 'self' instead 'static' to make sure every sub
     * command add Command class as arguments.
     *
     * @param   string|AbstractCommand $command       The argument name or Console object.
     *                                                If we just send a string, the object will auto create.
     * @param   null                   $description   Console description.
     * @param   array                  $options       Console options.
     * @param   \Closure               $code          The closure to execute.
     *
     * @return  AbstractCommand  Return this object to support chaining.
     *
     * @since   2.0
     */
    public function addCommand($command, $description = null, $options = [], \Closure $code = null)
    {
        if (is_string($command) && class_exists($command)) {
            $command = Ioc::make($command);
        }

        return parent::addCommand($command, $description, $options, $code);
    }
}
