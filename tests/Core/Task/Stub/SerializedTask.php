<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Task\Stub;

use DomainException;

/**
 * Serializable task fixture for command input, result and failure tests.
 * Remove when serialized task command behavior is no longer tested.
 */
class SerializedTask
{
    public function __construct(
        private mixed $value = null,
        private bool $fail = false,
        private bool $output = false,
    ) {}

    public function __invoke(): mixed
    {
        if ($this->output) {
            echo "task output\n";
            fwrite(STDOUT, "direct stdout\n");
            fwrite(STDERR, "direct stderr\n");
        }

        if ($this->fail) {
            throw new DomainException('Task failed.', 42);
        }

        return $this->value;
    }
}
