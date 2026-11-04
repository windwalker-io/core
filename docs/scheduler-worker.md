> Draft: to be merged into the official docs, then removed.

## Resident scheduler and execution protection

`SchedulerRunner` executes configured schedules in a resident process, without
requiring system CRON or a queue worker. Use it when a process manager can keep
the scheduler running and restart it after deployments.

Task locks prevent simultaneous execution, while scheduled-minute claims
prevent another process from repeating the same task's minute after its first
attempt has finished. Both protections apply to `schedule:worker` and
`schedule:run` automatically; no task-level opt-in is required.

### Running the worker

The application must have `dragonmantank/cron-expression ^3.1` installed and
its `schedules` routes configured.

```sh
php windwalker schedule:worker
php windwalker schedule:worker cleanup reports --tags=maintenance --tz=UTC -v
php windwalker schedule:worker --once
php windwalker schedule:worker --lifetime=3600 --max-runs=60
```

The command remains in the foreground. Use Supervisor, systemd or another
process manager for background operation; it does not daemonize itself.
Schedule definitions are loaded once per runner invocation. Restart the process
after changing routes or code.

The worker checks the current minute immediately, then polls once per second.
Due tasks execute sequentially through the application container. A long-running
task delays subsequent tasks; missed minutes are not replayed. No work is
recovered for periods when the worker was stopped.

| Argument or option | Default | Behavior |
|--------------------|---------|----------|
| `names` | All names | Optional positional task names |
| `--tags` | All tasks | Comma-separated tags; tasks must contain every requested tag |
| `--tz` | No timezone override | Timezone used to evaluate CRON expressions |
| `--once`, `-o` | Off | Check due tasks once and exit; does not force CRON matching |
| `--lifetime`, `-L` | `0` | Maximum lifetime in seconds; `0` means unlimited |
| `--max-runs` | `0` | Maximum minute ticks, including empty ticks; `0` means unlimited |

Limits must be non-negative integers. Lifetime uses elapsed monotonic time and
is checked between ticks, not during task execution. If wall-clock time moves
backwards, the runner waits until it passes the last processed minute.

Startup always displays the PID, shutdown instruction and settings table at
normal verbosity. Operation afterward is silent unless verbosity is enabled:

| Verbosity | Additional output |
|-----------|-------------------|
| `-v` | Lifecycle messages, task attempts, successful completion with duration, and skip reasons |
| `-vv` | Minute ticks, shutdown reason and tick count |

Errors remain visible without `-v`. Standard quiet/silent options suppress the
startup display; verbosity does not disable logging or lifecycle events.

With `pcntl` available, SIGINT and SIGTERM request graceful shutdown after the
active task. Without it, use runner limits or process-manager shutdown.

### Calling the runner from PHP

Resolve `SchedulerRunner` through DI and pass a `SchedulerOptions` object:

```php
<?php

declare(strict_types=1);

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Schedule\SchedulerOptions;
use Windwalker\Core\Schedule\SchedulerRunner;

function runScheduledTasks(ApplicationInterface $app): void
{
    $runner = $app->make(SchedulerRunner::class);
    $runner->run(
        new SchedulerOptions(
            lifetime: 3600,
            names: ['cleanup'],
            tags: ['maintenance'],
            timezone: 'UTC',
            once: false,
            maxRuns: 60,
        )
    );
}
```

The six options are public typed properties. `timezone` accepts
`DateTimeZone|string|null`; `names` and `tags` are arrays. Calling `run()` without
arguments uses unlimited lifetime/ticks, all names/tags, no timezone override,
and resident mode.

Options are captured at startup. Changing the options object during execution
affects only later calls to `run()`. Calling `run()` while the same runner is
already running throws `LogicException`.

Request a graceful stop with `$runner->stop('Maintenance requested.')`.
An active handler is allowed to finish; subsequent tasks are not started.

### Observing execution

Register listeners with `$runner->on()` or subscribers with
`$runner->subscribe()` before calling `run()`. The worker command also forwards
these events to the application dispatcher.

All event classes below are in `Windwalker\Core\Schedule\Event`. Payload
properties are read-only. Task event `time` is the tick's scheduled time, not
the time at which an individual handler finishes.

| Event | Payload and timing |
|-------|--------------------|
| `StartEvent` | `runner`, `schedule`; once after definitions are loaded |
| `LoopStartEvent` | `runner`, `time`, `run`; before each tick, numbered from 1 |
| `BeforeScheduleRunEvent` | `runner`, `scheduleEvent`, `time`; before checking task protection |
| `AfterScheduleRunEvent` | Task context plus `result`, `duration` in seconds; success only |
| `ScheduleSkippedEvent` | Task context plus `reason`; lock contention or an existing claim |
| `ScheduleFailureEvent` | Task context plus `exception`, `duration`; after logging and before `catch()` |
| `LoopEndEvent` | `runner`, `time`, `run`, nullable `exception`; also emitted when a tick throws |
| `StopEvent` | `runner`, `reason`, `runs`, nullable `exception`; after the runner stops |

For example, attach skip reporting to a DI-resolved runner:

```php
use Windwalker\Core\Schedule\Event\ScheduleSkippedEvent;
use Windwalker\Core\Schedule\SchedulerRunner;

function observeSkippedTasks(SchedulerRunner $runner): void
{
    $runner->on(
        ScheduleSkippedEvent::class,
        static function (ScheduleSkippedEvent $event): void {
            printf(
                "%s: %s\n",
                $event->scheduleEvent->getName(),
                $event->reason->value,
            );
        }
    );
}
```

`ScheduleSkipReason::LOCKED` means the task is already running.
`ScheduleSkipReason::CLAIMED` means its scheduled minute was already claimed.
Skipped tasks produce neither success nor failure events and do not invoke
the task's `catch()` callback.

Handler and protection-backend failures are logged and reported. The runner
invokes a configured `catch()` callback and continues to later tasks unless
that callback throws. Listener exceptions propagate and stop the runner.
A before-run listener can call `stop()` to prevent the handler from starting.

### Task locks and minute claims

Execution follows this order:

```text
Acquire task lock without waiting
  -> Atomically claim the scheduled minute
    -> Resolve and invoke the handler
  -> Release task lock in finally
```

If the task lock is occupied, that invocation is skipped rather than queued.
An already-taken claim also skips execution and releases the acquired task
lock. The claim remains after either success or failure.

Claims identify an application namespace, a unique task name and
`floor(scheduledTimestamp / 60)`. UTC timestamp minutes distinguish dates and
timezone offsets. Every task in a tick uses the same scheduled time, even if
earlier tasks take several minutes.

| Default service | Storage and lifetime |
|-----------------|----------------------|
| `SchedulerLock` | Symfony `FlockStore` in `@temp/scheduler-locks`; held during the handler, no TTL, released on process exit |
| `SchedulerClaim` | Per-task file ledger in `@temp/scheduler-claims`; claims retained for 86,400 seconds from acquisition |

The claim ledger uses a short blocking Flock guard for its update, not for
handler execution. Expired entries are pruned when the next new claim is
persisted. Invalid claim data and backend failures throw rather than permitting
an unprotected execution.

Both defaults are **single-host**. Their default namespace is derived from
`@root`; cooperating processes must use the same namespace and storage.
Do not remove lock, guard or claim files while workers are running. Removing
claim data allows previously claimed minutes to execute again.

### Customizing protection through DI

Override these bindings in `di.config.php`. For example, configure the local
implementations with an explicit namespace and a longer retention window:

```php
<?php

declare(strict_types=1);

use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Schedule\SchedulerClaim;
use Windwalker\Core\Schedule\SchedulerClaimInterface;
use Windwalker\Core\Schedule\SchedulerLock;
use Windwalker\Core\Schedule\SchedulerLockInterface;

return [
    'bindings' => [
        SchedulerLockInterface::class => static fn(PathResolver $paths): SchedulerLock =>
            new SchedulerLock($paths, namespace: 'my-app'),
        SchedulerClaimInterface::class => static fn(PathResolver $paths): SchedulerClaim =>
            new SchedulerClaim($paths, retention: 172800, namespace: 'my-app'),
    ],
];
```

`SchedulerClaim` requires positive retention in seconds. An explicit namespace
does not make local storage distributed.

For Redis or database protection, bind your own implementations:

| Contract | Implementation requirement |
|----------|----------------------------|
| `SchedulerLockInterface::createLock(ScheduleEvent $event): LockInterface` | Return a fresh, unacquired Symfony lock; Core acquires it non-blocking and releases it |
| `SchedulerClaimInterface::claim(ScheduleEvent $event, DateTimeImmutable $scheduledAt): bool` | Atomically retain the task-minute claim; return `false` only for duplicates, and throw on backend errors |

A shared claim store can use Redis `SET ... NX EX` or a database INSERT with a
unique constraint on namespace, task and minute. Claim storage and expiry are
the implementation's responsibility; Core does not release claims.

Replacing only the task lock does not provide cross-host minute deduplication.
Replace **both** contracts, use common storage and identical task/application
identities on every host, and keep clocks synchronized. Deployment paths may
differ, so do not rely on a root-path namespace for distributed implementations.

For expiring execution locks, ensure the TTL covers the handler runtime.
The runner does not automatically refresh locks. Protection covers the handler,
not later lifecycle listeners, `catch()` callbacks or background jobs dispatched
by the handler.

### Starting an Enqueuer subprocess

`Windwalker\Core\Queue\ScheduleEnqueuer::runByCommand()` starts
`queue:enqueuer` in a subprocess and waits for it to finish. This reuses the
command's configured queue connection, `queue.enqueuer.handlers`, DI invoker,
maintenance handling and lifecycle scripts.

```php
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Queue\ScheduleEnqueuer;
use Windwalker\Core\Queue\ScheduleEnqueuerOptions;

$schedule->task('enqueuer')
    ->everyMinutes(5)
    ->handler(
        function (ConsoleApplication $app, ScheduleEnqueuer $enqueuer): void {
            $enqueuer->setMessageOutput($app->getOutput());
            $enqueuer->runByCommand(
                5 * 60 - 3,
                options: new ScheduleEnqueuerOptions(sleep: 2)
            );
        }
    );
```

The method accepts `lifetime` in seconds, optional `connection`, optional
`channels` (`string|array|null`), and `ScheduleEnqueuerOptions`. With no connection
or channels supplied, the command uses `queue.default` and the `default` channel.
Use `channels: '*'` to execute all registered channel handlers.
Channel names must be non-empty strings without a leading hyphen.

| Option | Default |
|--------|---------|
| `once` | `false`; execute each selected channel once and exit |
| `force` | `false` |
| `memoryLimit` | `128` MB |
| `sleep` | `1.0` seconds; fractional intervals are supported |
| `timeout` | `60` seconds |
| `maxRuns` | `0` (unlimited) |
| `shuffleChannels` | `false` |
| `stopWhenEmpty` | `false`; the underlying Enqueuer uses handler return values to determine emptiness |

Limits and sleep must be non-negative, and sleep must be finite. Numeric values,
including zero, are forwarded explicitly to the command. A zero lifetime means
unlimited; the lifetime parameter is not a hard subprocess deadline.
The underlying runner checks lifetime between iterations, so slow handlers and
sleep can extend execution beyond the requested interval.

Both output streams are forwarded through `MessageOutputTrait` without adding
newlines. Non-zero subprocess exits propagate through `mustRunProcess()` to the
scheduler failure flow. Individual Enqueuer handler failures may be reported by
the command while it continues running; a successful subprocess exit does not
mean every handler succeeded.

The subprocess is synchronous and holds the outer task lock until it exits.
Run long-lived producers in a dedicated scheduler process, for example
`php windwalker schedule:worker enqueuer`, to avoid delaying unrelated tasks.
The outer minute claim prevents duplicate launches of that scheduled minute,
not duplicate job production across the Enqueuer's polling iterations.
There is no in-process `run()` or `runOnce()` adapter.

### Direct execution and delivery limits

`ScheduleService::runEvent(ScheduleEvent $event, ?DateTimeImmutable $scheduledAt = null): mixed`
applies both protections and returns the handler result. If no time is supplied,
it captures the current Core clock before acquisition. Direct callers receive
`ScheduleSkippedException` with a read-only `reason` for an ordinary skip;
backend and handler failures propagate. Direct service calls do not emit runner
lifecycle events.

`schedule:run --clock="2026-11-04T06:08:00Z"` uses one explicit time for CRON
evaluation and claims. Use `--clock` instead of deprecated `--time`.
`--test` bypasses CRON matching, **not** locks or claims, so repeated tests of
the same task-minute may be skipped.

Within the retention window, the claim provides **at most one execution
attempt**, not exactly-once delivery. A process can crash after claiming but
before completing its handler. After retention expires, an old scheduled minute
can be claimed again. Choose retention to cover the supported delay/retry
window; use durable dispatch, retries and idempotent handlers when delivery
must be guaranteed.
