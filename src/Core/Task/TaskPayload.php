<?php

declare(strict_types=1);

namespace Windwalker\Core\Task;

use ErrorException;
use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;
use Windwalker\Core\Application\ApplicationInterface;

use function Windwalker\serialize;
use function Windwalker\unserialize;

class TaskPayload
{
    public function __construct(private ApplicationInterface $app) {}

    public function encode(object $task): string
    {
        if (!is_callable($task)) {
            throw new InvalidArgumentException('Task must be a Closure or an object with a public __invoke() method.');
        }

        return $this->encodeData($task);
    }

    public function decode(string $payload): object
    {
        $task = $this->decodeData($payload);

        if (!is_object($task) || !is_callable($task)) {
            throw new UnexpectedValueException('Task must be a Closure or an object with a public __invoke() method.');
        }

        return $task;
    }

    public function encodeRequest(object $task, string $taskId, int $ttl = 300): string
    {
        if (!is_callable($task)) {
            throw new InvalidArgumentException('Task must be callable.');
        }

        $this->validateDestination($taskId, $ttl);

        return $this->encodeData(['task' => $task, 'taskId' => $taskId, 'ttl' => $ttl]);
    }

    /** @return array{task: object, taskId: string, ttl: int} */
    public function decodeRequest(string $payload): array
    {
        $request = $this->decodeData($payload);

        if (
            !is_array($request)
            || !is_object($request['task'] ?? null)
            || !is_callable($request['task'])
            || !is_string($request['taskId'] ?? null)
            || !is_int($request['ttl'] ?? null)
        ) {
            throw new UnexpectedValueException('Invalid task IPC request.');
        }

        $this->validateDestination($request['taskId'], $request['ttl']);

        return $request;
    }

    private function validateDestination(string $taskId, int $ttl): void
    {
        if (preg_match('/\A[a-f0-9]{32}\z/', $taskId) !== 1 || $ttl <= 0) {
            throw new InvalidArgumentException('Task ID must be 32 hexadecimal characters and TTL must be positive.');
        }
    }

    private function encodeData(mixed $data): string
    {
        // Wrap closures nested in IPC request envelopes as well as standalone tasks.
        if (is_array($data) && $data['task'] instanceof \Closure) {
            $data['task'] = \Windwalker\closure($data['task']);
        }

        $key = $this->getSigningKey();
        $body = 'v1.' . base64_encode(serialize($data));

        return $body . '.' . hash_hmac('sha256', $body, $key);
    }

    private function decodeData(string $payload): mixed
    {
        $parts = explode('.', trim($payload));

        if (
            count($parts) !== 3
            || $parts[0] !== 'v1'
            || $parts[1] === ''
            || preg_match('/\A[a-f0-9]{64}\z/', $parts[2]) !== 1
        ) {
            throw new InvalidArgumentException('Task payload must be a signed v1 payload.');
        }

        $expected = hash_hmac('sha256', $parts[0] . '.' . $parts[1], $this->getSigningKey());

        if (!hash_equals($expected, $parts[2])) {
            throw new InvalidArgumentException('Task payload signature is invalid.');
        }

        $serialized = base64_decode($parts[1], true);

        if ($serialized === false || $serialized === '') {
            throw new InvalidArgumentException('Task payload must contain non-empty Base64-encoded serialized data.');
        }

        // Verify the entire envelope before unserialize can execute any object hooks.
        set_error_handler(
            static function (int $severity, string $message, string $file, int $line): never {
                throw new ErrorException($message, 0, $severity, $file, $line);
            },
            E_WARNING | E_NOTICE
        );

        try {
            $task = unserialize($serialized, ['allowed_classes' => true]);
        } finally {
            restore_error_handler();
        }

        if (is_array($task) && ($task['task'] ?? null) instanceof \Laravel\SerializableClosure\SerializableClosure) {
            $task['task'] = $task['task']->getClosure();
        }

        return $task;
    }

    private function getSigningKey(): string
    {
        if ($this->app->getSecret() === '') {
            throw new LogicException('An application secret is required to sign and verify task payloads.');
        }

        return $this->app->getSecret('task.run');
    }
}
