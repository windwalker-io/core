<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Console;

use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Event\CoreEventAwareTrait;

/**
 * Trait MessageOutputTrait
 */
trait MessageOutputTrait
{
    use CoreEventAwareTrait;

    public function setMessageOutput(OutputInterface|ApplicationInterface $output): static
    {
        $this->onMessages(
            function (MessageOutputEvent $event) use ($output) {
                $event->writeWith($output);
            }
        );

        return $this;
    }

    public function onMessages(callable $handler): static
    {
        $this->on(MessageOutputEvent::class, $handler);
        $this->on(ErrorMessageOutputEvent::class, $handler);

        return $this;
    }

    public function emitMessage(string|array $messages, bool $newLine = true, int $options = 0): MessageOutputEvent
    {
        return $this->emit(new MessageOutputEvent($messages, $newLine, $options));
    }

    public function emitErrorMessage(
        string|array $messages,
        bool $newLine = true,
        int $options = 0
    ): ErrorMessageOutputEvent {
        return $this->emit(new ErrorMessageOutputEvent($messages, $newLine, $options));
    }
}
