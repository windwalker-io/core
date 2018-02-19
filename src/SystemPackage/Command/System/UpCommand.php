<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\SystemPackage\Command\System;

/**
 * The UpCommand class.
 *
 * @since  3.0
 */
class UpCommand extends DownCommand
{
    /**
     * Console(Argument) name.
     *
     * @var  string
     */
    protected $name = 'up';

    /**
     * The command description.
     *
     * @var  string
     */
    protected $description = 'Make site online.';

    /**
     * The usage to tell user how to use this command.
     *
     * @var string
     */
    protected $usage = '%s [options]';

    /**
     * Property offline.
     *
     * @var  boolean
     */
    protected $offline = false;
}
