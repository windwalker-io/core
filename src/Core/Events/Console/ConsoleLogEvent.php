<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Events\Console;

/**
 * The ConsoleLogEvent class.
 */
class ConsoleLogEvent
{
    /**
     * MessageEvent constructor.
     *
     * @param  string|array  $messages
     * @param  string        $type
     */
    public function __construct(
        protected string|array $messages,
        protected ?string $type = null,
    ) {
    }

    public function getMessages(): array
    {
        return (array) $this->messages;
    }

    /**
     * @param  array|string  $messages
     *
     * @return  static  Return self to support chaining.
     */
    public function setMessages(array|string $messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param  string  $type
     *
     * @return  static  Return self to support chaining.
     */
    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
