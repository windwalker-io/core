<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Event;

use Windwalker\Event\EventAwareTrait;

/**
 * Trait MessageEventTrait
 */
trait MessageEventTrait
{
    use EventAwareTrait;

    public function addMessage(string|array $messages, ?string $type = null): MessageEvent
    {
        return $this->emit(new MessageEvent($messages, $type));
    }
}
