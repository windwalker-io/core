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
 * Trait MessageEventTrait
 */
trait ConsoleLogTrait
{
    use EventAwareTrait;

    public function consoleLog(string|array $messages, ?string $type = null): ConsoleLogEvent
    {
        return $this->emit(new ConsoleLogEvent($messages, $type));
    }
}
