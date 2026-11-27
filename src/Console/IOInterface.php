<?php

declare(strict_types=1);

namespace Windwalker\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interface IOInterface
 */
interface IOInterface extends InputInterface, OutputInterface
{
    /**
     * Get style output.
     *
     * @return  SymfonyStyle
     */
    public function style(): SymfonyStyle;

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
     * @return  InputInterface
     */
    public function getInput(): InputInterface;

    /**
     * @return  OutputInterface
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
    public function extract(array|InputInterface|null $input = null, ?OutputInterface $output = null): static;

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

    public function askChoice(string|Question $question, array $options, mixed $default = null): mixed;

    /**
     * Arrow-key (↑/↓) single select menu.
     *
     * @param  string        $question
     * @param  array<mixed>  $options   Map of value => label, or list of labels.
     * @param  mixed         $default   Default value (key of $options).
     *
     * @return  mixed  The selected value (key of $options).
     */
    public function askSelect(string $question, array $options, mixed $default = null): mixed;

    /**
     * Multiple choice checkbox menu.
     *
     * @param  string        $question
     * @param  array<mixed>  $options   Map of value => label.
     * @param  array<mixed>  $default   Default values (keys of $options).
     *
     * @return  array  The selected values (keys of $options).
     */
    public function multiSelect(string $question, array $options, array $default = []): array;
}
