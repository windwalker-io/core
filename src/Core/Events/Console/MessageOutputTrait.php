<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Console;

use Windwalker\Core\Event\CoreEventAwareTrait;

/**
 * Trait MessageOutputTrait
 */
trait MessageOutputTrait
{
    use CoreEventAwareTrait;

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
