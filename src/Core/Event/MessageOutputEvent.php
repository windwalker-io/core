<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Event;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * The ConsoleOutputEvent class.
 */
class MessageOutputEvent
{
    /**
     * ConsoleOutputEvent constructor.
     *
     * @param  string|array  $messages
     * @param  bool          $newLine
     * @param  int           $options
     */
    public function __construct(public string|array $messages, public bool $newLine = true, public int $options = 0)
    {
    }

    /**
     * Instant write to std output.
     *
     * @param  OutputInterface  $output
     *
     * @return  static
     */
    public function writeWith(OutputInterface $output): static
    {
        $output->write($this->messages, $this->newLine, $this->options);

        return $this;
    }
}