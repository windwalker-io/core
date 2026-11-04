<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Task\Stub;

use RuntimeException;

/**
 * Subprocess fixture for runner timing, output, termination and malformed results.
 * Remove when TaskRunner integration tests are removed.
 */
class RunnerTask
{
    public function __construct(
        private string $mode,
        private mixed $value = null,
        private ?string $marker = null,
        private ?string $peer = null
    ) {}

    public function __invoke(): mixed
    {
        if ($this->mode === 'barrier') {
            file_put_contents($this->marker, (string) getmypid());
            $deadline = microtime(true) + 5;

            while (!is_file($this->peer)) {
                if (microtime(true) >= $deadline) {
                    throw new RuntimeException('Sibling task did not start concurrently.');
                }

                usleep(1000);
            }
        } elseif ($this->mode === 'sleep') {
            usleep(500000);
        } elseif ($this->mode === 'exit') {
            exit(0);
        } elseif ($this->mode === 'output' || $this->mode === 'output-fail') {
            echo str_repeat('o', 200000);
            fwrite(STDERR, str_repeat('e', 200000));

            if ($this->mode === 'output-fail') {
                throw new RuntimeException('Noisy task failed.');
            }
        }

        return $this->value;
    }
}
