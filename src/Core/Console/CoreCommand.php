<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Console;

use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The CoreCommand class.
 */
abstract class CoreCommand extends Command
{
    public ?IOInterface $io = null;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->init();
    }

    abstract protected function init(): void;

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $code = $this->doExecute($this->io = new IO($input, $output, $this));

        if (is_bool($code)) {
            return $code ? 0 : 255;
        }

        return $code;
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  mixed
     */
    abstract protected function doExecute(IOInterface $io): mixed;
}
