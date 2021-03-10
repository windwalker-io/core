<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Console;

use Symfony\Component\Console\Command\Command;

/**
 * Interface CommandInterface
 */
interface CommandWrapperInterface
{
    /**
     * configure
     *
     * @param  Command  $command
     *
     * @return  void
     */
    public function configure(Command $command): void;

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  mixed
     */
    public function execute(IOInterface $io): mixed;
}
