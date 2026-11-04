<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use InvalidArgumentException;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\DI\Attributes\Service;

#[Service]
class ScheduleEnqueuer
{
    use MessageOutputTrait;

    public function __construct(protected ApplicationInterface $app) {}

    public function runByCommand(
        int $lifetime,
        ?string $connection = null,
        array|string|null $channels = null,
        ScheduleEnqueuerOptions $options = new ScheduleEnqueuerOptions()
    ): void {
        if (
            $lifetime < 0 || $options->memoryLimit < 0 || $options->timeout < 0
            || $options->maxRuns < 0 || !is_finite($options->sleep) || $options->sleep < 0
        ) {
            throw new InvalidArgumentException('Enqueuer limits and sleep must be finite and non-negative.');
        }

        $arguments = ['php', 'windwalker', 'queue:enqueuer'];

        foreach ((array) $channels as $channel) {
            if (!is_string($channel) || $channel === '' || str_starts_with($channel, '-')) {
                throw new InvalidArgumentException('Enqueuer channel names must be non-empty strings without a leading hyphen.');
            }

            $arguments[] = escapeshellarg($channel);
        }

        $cliOptions = [
            '--lifetime' => $lifetime,
            '--memory' => $options->memoryLimit,
            '--sleep' => $options->sleep,
            '--timeout' => $options->timeout,
            '--max-runs' => $options->maxRuns,
        ];

        if ($connection !== null) {
            if ($connection === '') {
                throw new InvalidArgumentException('Enqueuer connection must not be empty.');
            }

            $cliOptions['--connection'] = $connection;
        }

        foreach ($cliOptions as $name => $value) {
            $arguments[] = $name;
            $arguments[] = escapeshellarg((string) $value);
        }

        foreach ([
            '--once' => $options->once,
            '--force' => $options->force,
            '--shuffle-channels' => $options->shuffleChannels,
            '--stop-when-empty' => $options->stopWhenEmpty,
        ] as $name => $enabled) {
            if ($enabled) {
                $arguments[] = $name;
            }
        }

        $this->app->mustRunProcess(
            implode(' ', $arguments),
            output: function ($type, $buffer) {
                $this->emitMessage($buffer, false);
            }
        );
    }
}
