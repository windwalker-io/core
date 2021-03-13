<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Console\Process;

use Symfony\Component\Process\Process;

/**
 * Interface ProcessRunnerInterface
 */
interface ProcessRunnerInterface
{
    /**
     * createProcess
     *
     * @param  string|array  $script
     *
     * @return  Process
     *
     * @since  3.5.22
     */
    public function createProcess(string|array $script): Process;

    /**
     * runProcess
     *
     * @param  string|array  $script
     * @param  string|null   $input
     * @param  bool          $output
     *
     * @return Process
     *
     * @since  3.5.5
     */
    public function runProcess(string|array $script, ?string $input = null, bool $output = true): Process;
}
