<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
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

    /**
     * ask
     *
     * @param  string|Question  $question
     * @param  string|null      $default
     *
     * @return  mixed
     */
    public function ask(string|Question $question, ?string $default = null): mixed;

    /**
     * askConfirmation
     *
     * @param  string|ConfirmationQuestion  $question
     * @param  bool                         $default
     *
     * @return  bool
     */
    public function askConfirmation(
        string|ConfirmationQuestion $question,
        bool $default = true,
        string $trueAnswerRegex = '/^y/i'
    ): bool;

    /**
     * askAndValidate
     *
     * @param  string|Question  $question
     * @param  mixed            $validator
     * @param  int|null         $attempts
     * @param  string|null      $default
     *
     * @return  mixed
     */
    public function askAndValidate(
        string|Question $question,
        callable $validator,
        ?int $attempts = null,
        ?string $default = null
    ): mixed;

    /**
     * askAndHideAnswer
     *
     * @param  string|Question  $question
     *
     * @return  string
     */
    public function askAndHideAnswer(string|Question $question): mixed;
}
