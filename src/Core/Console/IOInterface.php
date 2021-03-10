<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * Interface IOInterface
 */
interface IOInterface extends InputInterface, OutputInterface
{
    /**
     * Get style output.
     *
     * @return  StyleInterface
     */
    public function style(): StyleInterface;

    /**
     * Get error style.
     *
     * @return  StyleInterface
     */
    public function errorStyle(): StyleInterface;
}
