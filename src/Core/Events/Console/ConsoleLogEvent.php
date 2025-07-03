<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Console;

use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The ConsoleLogEvent class.
 */
class ConsoleLogEvent
{
    use AccessorBCTrait;

    public array $messages {
        set (mixed $value) {
            $this->messages = (array) $value;
        }
    }

    /**
     * MessageEvent constructor.
     *
     * @param  array|string  $messages
     * @param  ?string       $type
     */
    public function __construct(
        array|string $messages,
        public ?string $type = null,
    ) {
        $this->messages = $messages;
    }
}
