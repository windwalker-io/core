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
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The IO class.
 */
class IO implements IOInterface
{
    protected SymfonyStyle|null $style = null;

    /**
     * IO constructor.
     *
     * @param  InputInterface   $input
     * @param  OutputInterface  $output
     * @param  Command          $command
     */
    public function __construct(
        protected InputInterface $input,
        protected OutputInterface $output,
        protected Command $command,
    ) {
        //
    }

    /**
     * out
     *
     * @param  string|array  $messages
     * @param  bool          $nl
     * @param  int           $options
     *
     * @return  static
     */
    public function out(string|array $messages, bool $nl = true, int $options = 0): static
    {
        foreach ((array) $messages as $message) {
            $this->output->write($message, $nl, $options);
        }

        return $this;
    }

    /**
     * ask
     *
     * @param  string|Question  $question
     * @param  string|null      $default
     *
     * @return  mixed
     */
    public function ask(string|Question $question, ?string $default = null): mixed
    {
        if (is_string($question)) {
            $question = new Question($question, $default);
        }

        return $this->getQuestionHelper()->ask(
            $this->input,
            $this->output,
            $question
        );
    }

    protected function getQuestionHelper(): QuestionHelper
    {
        return $this->command->getHelper('question');
    }

    /**
     * @return InputInterface
     */
    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * @param  InputInterface  $input
     *
     * @return  static  Return self to support chaining.
     */
    public function setInput(InputInterface $input): static
    {
        $this->input = $input;

        $this->style = null;

        return $this;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @param  OutputInterface  $output
     *
     * @return  static  Return self to support chaining.
     */
    public function setOutput(OutputInterface $output): static
    {
        $this->output = $output;

        $this->style = null;

        return $this;
    }

    /**
     * Returns the first argument from the raw parameters (not parsed).
     *
     * @return string|null The value of the first argument or null otherwise
     */
    public function getFirstArgument(): ?string
    {
        return $this->input->getFirstArgument();
    }

    /**
     * Returns true if the raw parameters (not parsed) contain a value.
     *
     * This method is to be used to introspect the input parameters
     * before they have been validated. It must be used carefully.
     * Does not necessarily return the correct result for short options
     * when multiple flags are combined in the same option.
     *
     * @param  string|array  $values      The values to look for in the raw parameters (can be an array)
     * @param  bool          $onlyParams  Only check real parameters, skip those following an end of options (--) signal
     *
     * @return bool true if the value is contained in the raw parameters
     */
    public function hasParameterOption($values, bool $onlyParams = false): bool
    {
        return $this->input->hasParameterOption($values, $onlyParams);
    }

    /**
     * Returns the value of a raw option (not parsed).
     *
     * This method is to be used to introspect the input parameters
     * before they have been validated. It must be used carefully.
     * Does not necessarily return the correct result for short options
     * when multiple flags are combined in the same option.
     *
     * @param  string|array  $values      The value(s) to look for in the raw parameters (can be an array)
     * @param  mixed         $default     The default value to return if no result is found
     * @param  bool          $onlyParams  Only check real parameters, skip those following an end of options (--) signal
     *
     * @return mixed The option value
     */
    public function getParameterOption($values, $default = false, bool $onlyParams = false)
    {
        return $this->input->getParameterOption($values, $default, $onlyParams);
    }

    /**
     * Binds the current Input instance with the given arguments and options.
     *
     * @param  InputDefinition  $definition
     *
     * @return mixed
     */
    public function bind(InputDefinition $definition): void
    {
        $this->input->bind($definition);
    }

    /**
     * Validates the input.
     *
     * @throws RuntimeException When not enough arguments are given
     */
    public function validate(): void
    {
        $this->input->validate();
    }

    /**
     * Returns all the given arguments merged with the default values.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->input->getArguments();
    }

    /**
     * Returns the argument value for a given argument name.
     *
     * @return string|string[]|null The argument value
     *
     * @throws InvalidArgumentException When argument given doesn't exist
     */
    public function getArgument(string $name): string|array|null
    {
        return $this->input->getArgument($name);
    }

    /**
     * Sets an argument value by name.
     *
     * @param  string|string[]|null  $value  The argument value
     *
     * @throws InvalidArgumentException When argument given doesn't exist
     */
    public function setArgument(string $name, $value): void
    {
        $this->input->setArgument($name, $value);
    }

    /**
     * Returns true if an InputArgument object exists by name or position.
     *
     * @param  string|int  $name  The InputArgument name or position
     *
     * @return bool true if the InputArgument object exists, false otherwise
     */
    public function hasArgument($name): bool
    {
        return $this->input->hasArgument($name);
    }

    /**
     * Returns all the given options merged with the default values.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->input->getOptions();
    }

    /**
     * Returns the option value for a given option name.
     *
     * @return string|string[]|bool|null The option value
     *
     * @throws InvalidArgumentException When option given doesn't exist
     */
    public function getOption(string $name): string|array|bool|null
    {
        return $this->input->getOption($name);
    }

    /**
     * Sets an option value by name.
     *
     * @param  string|string[]|bool|null  $value  The option value
     *
     * @throws InvalidArgumentException When option given doesn't exist
     */
    public function setOption(string $name, $value): void
    {
        $this->input->setOption($name, $value);
    }

    /**
     * Returns true if an InputOption object exists by name.
     *
     * @return bool true if the InputOption object exists, false otherwise
     */
    public function hasOption(string $name): bool
    {
        return $this->input->hasOption($name);
    }

    /**
     * Is this input means interactive?
     *
     * @return bool
     */
    public function isInteractive(): bool
    {
        return $this->input->isInteractive();
    }

    /**
     * Sets the input interactivity.
     */
    public function setInteractive(bool $interactive): void
    {
        $this->input->setInteractive($interactive);
    }

    /**
     * Writes a message to the output.
     *
     * @param  string|iterable  $messages  The message as an iterable of strings or a single string
     * @param  bool             $newline   Whether to add a newline
     * @param  int              $options   A bitmask of options (one of the OUTPUT or VERBOSITY constants), 0 is
     *                                     considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    public function write($messages, bool $newline = false, int $options = 0): void
    {
        $this->output->write($messages, $newline, $options);
    }

    /**
     * Writes a message to the output and adds a newline at the end.
     *
     * @param  string|iterable  $messages  The message as an iterable of strings or a single string
     * @param  int              $options   A bitmask of options (one of the OUTPUT or VERBOSITY constants), 0 is
     *                                     considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    public function writeln($messages, int $options = 0): void
    {
        $this->output->writeln($messages, $options);
    }

    /**
     * Sets the verbosity of the output.
     */
    public function setVerbosity(int $level): void
    {
        $this->output->setVerbosity($level);
    }

    /**
     * Gets the current verbosity of the output.
     *
     * @return int The current level of verbosity (one of the VERBOSITY constants)
     */
    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    /**
     * Returns whether verbosity is quiet (-q).
     *
     * @return bool true if verbosity is set to VERBOSITY_QUIET, false otherwise
     */
    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    /**
     * Returns whether verbosity is verbose (-v).
     *
     * @return bool true if verbosity is set to VERBOSITY_VERBOSE, false otherwise
     */
    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    /**
     * Returns whether verbosity is very verbose (-vv).
     *
     * @return bool true if verbosity is set to VERBOSITY_VERY_VERBOSE, false otherwise
     */
    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    /**
     * Returns whether verbosity is debug (-vvv).
     *
     * @return bool true if verbosity is set to VERBOSITY_DEBUG, false otherwise
     */
    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    /**
     * Sets the decorated flag.
     */
    public function setDecorated(bool $decorated): void
    {
        $this->output->setDecorated($decorated);
    }

    /**
     * Gets the decorated flag.
     *
     * @return bool true if the output will decorate messages, false otherwise
     */
    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        $this->output->setFormatter($formatter);
    }

    /**
     * Returns current output formatter instance.
     *
     * @return OutputFormatterInterface
     */
    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }

    /**
     * @inheritDoc
     */
    public function style(): SymfonyStyle
    {
        return $this->style ??= new SymfonyStyle($this->input, $this->output);
    }

    /**
     * @inheritDoc
     */
    public function errorStyle(): SymfonyStyle
    {
        return $this->style()->getErrorStyle();
    }

    /**
     * Get wrapper command.
     *
     * @return  Command
     */
    public function getWrapperCommand(): Command
    {
        return $this->command;
    }

    /**
     * extract
     *
     * @param  array|InputInterface|null  $input
     * @param  OutputInterface|null       $output
     *
     * @return  mixed
     */
    public function extract(array|InputInterface $input = null, ?OutputInterface $output = null): static
    {
        $newIO = clone $this;

        if (is_array($input)) {
            unset($input['command']);
            $input = new ArrayInput($input);
        }

        if ($input === null) {
            $input = $this->input;
        }

        $newIO->input  = $input ?? $this->input;
        $newIO->output = $output ?? $this->output;

        return $newIO;
    }

    /**
     * New line.
     *
     * @param  int  $count
     *
     * @return  void
     */
    public function newLine(int $count = 1): void
    {
        $this->style()->newLine($count);
    }
}
