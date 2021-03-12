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

    /**
     * New line.
     *
     * @param  int  $count
     *
     * @return  void
     */
    public function newLine(int $count = 1): void;

    /**
     * Get wrapper command.
     *
     * @return  Command
     */
    public function getWrapperCommand(): Command;

    /**
     * @return InputInterface
     */
    public function getInput(): InputInterface;

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface;

    /**
     * extract
     *
     * @param  array|InputInterface|null  $input
     * @param  OutputInterface|null       $output
     *
     * @return  mixed
     */
    public function extract(array|InputInterface $input = null, ?OutputInterface $output = null): static;
}
