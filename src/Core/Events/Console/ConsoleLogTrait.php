<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

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
        return $this->emit(new ConsoleLogEvent($messages, $type));
    }
}
