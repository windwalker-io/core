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

    /**
     * MessageEvent constructor.
     *
     * @param  array   $messages
     * @param  string  $type
     */
    public function __construct(
        public array $messages {
            get => $this->messages;
            set ($value) {
                $this->messages = (array) $value;
            }
        },
        public ?string $type = null,
    ) {
    }
}
