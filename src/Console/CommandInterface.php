<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Console;

use Symfony\Component\Console\Command\Command;

/**
 * Interface CommandInterface
 */
interface CommandInterface
{
    // see https://tldp.org/LDP/abs/html/exitcodes.html
    public const SUCCESS = 0;

    public const FAILURE = 1;

    public const INVALID = 2;

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
     * @return  int Return 0 is success, 1-255 is failure.
     */
    public function execute(IOInterface $io): int;
}
