<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Console;

use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Core\Application\ApplicationInterface;

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
     * @param  OutputInterface|ApplicationInterface  $output
     *
     * @return  static
     */
    public function writeWith(OutputInterface|ApplicationInterface $output): static
    {
        if ($output instanceof ApplicationInterface) {
            $messages = $this->messages;

            if (!$this->newLine) {
                $messages = implode(' ', (array) $messages);
            }

            $output->addMessage($messages);
        } else {
            $output->write($this->messages, $this->newLine, $this->options);
        }

        return $this;
    }
}
