<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Events\Console;

use Windwalker\Event\EventAwareTrait;

/**
 * Trait MessageOutputTrait
 */
trait MessageOutputTrait
{
    use EventAwareTrait;

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
