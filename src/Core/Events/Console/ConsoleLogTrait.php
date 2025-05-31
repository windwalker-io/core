<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Console;

use Windwalker\Core\Event\CoreEventAwareTrait;

/**
 * Trait MessageEventTrait
 */
trait ConsoleLogTrait
{
    use CoreEventAwareTrait;

    public function consoleLog(string|array $messages, ?string $type = null): ConsoleLogEvent
    {
        return $this->emit(new ConsoleLogEvent(messages: $messages, type: $type));
    }
}
