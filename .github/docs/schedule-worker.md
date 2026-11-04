# Schedule worker

`Windwalker\Core\Schedule\SchedulerRunner` runs the configured `schedules` routes
in a resident process without requiring system CRON or `windwalker/queue`.
The host application must install `dragonmantank/cron-expression ^3.1`, as
required by `schedule:run`.

```sh
php windwalker schedule:worker
php windwalker schedule:worker cleanup reports --tags=maintenance --tz=UTC
php windwalker schedule:worker --once
php windwalker schedule:worker --lifetime=3600 --max-runs=60
php windwalker schedule:worker -v
php windwalker schedule:worker -vv
```

On startup, the command displays its PID, stop instruction and a table of
names, tags, timezone, polling interval, run/lifetime limits and single-run mode,
even without `-v`. Normal operation afterward is silent by default.
`-v` shows lifecycle messages, event dispatch and completion with elapsed time. `-vv` additionally
shows each minute tick and the shutdown reason and tick count. Errors remain
visible without verbosity flags; logging and lifecycle events are unaffected.
Standard `--quiet` and `--silent` options still suppress startup information.

The worker checks the current minute immediately on startup, then polls once
per second and dispatches due events once per observed minute. Names and tags
use the same filtering rules as `schedule:run`. `--once` still checks CRON
expressions; it does not force events to run.

Events execute sequentially through the application container. Exceptions are
logged and reported, and the event's `catch()` callback receives the exception.
An unhandled event failure does not prevent subsequent events from running;
an exception thrown by the error callback stops the worker.

`--max-runs` counts minute ticks, including ticks with no matching events.
`--lifetime` uses elapsed monotonic time. Both limits default to unlimited and
must be non-negative integers. They are checked between ticks, not during a
running event.

On platforms with `pcntl`, SIGINT and SIGTERM request a graceful stop after
the current event. Without `pcntl`, use the limits or process-manager shutdown.
PHP callers can use `$worker->stop()` and forward output with
`$worker->setMessageOutput($output)`.

Use a process manager such as Supervisor or systemd to keep the command running
in the background and restart it after failures or deployments. The command
does not daemonize itself. Schedule definitions are loaded once; restart the
process after changing routes or code. Avoid running multiple workers (or
system CRON's `schedule:run`) for the same schedules unless duplicate execution
is acceptable. Task locks prevent concurrent execution, not repeated execution
of the same scheduled minute.

If a task spans multiple minutes, intervening minutes are skipped rather than
replayed. The worker is not a durable job queue and does not guarantee delivery
of events missed while stopped.

If the wall clock moves backwards, dispatch waits until it passes the last
processed minute, so previously processed minutes are not repeated.

## Task locks

`ScheduleService::runEvent()` creates a fresh Symfony lock for each invocation
through `SchedulerLockInterface`, calls `acquire(blocking: true)`, and releases
it in `finally`. This protects both `schedule:worker` and `schedule:run`.
Handler return values are preserved; acquisition and release errors propagate.
The lock covers the handler, not the subsequent lifecycle listeners or `catch()`.

The default `SchedulerLock` uses Symfony `FlockStore` under
`@temp/scheduler-locks`, with an application-root namespace and the schedule name
as resource identity. All processes must use the same root and lock directory.
Files must remain in place while workers are running; do not purge this directory.
Flock locks do not expire and are released by the OS when the process exits.
This is a local-host default, not a distributed lock.

Blocking waits serialize competing invocations: a second worker waits and
then runs its own invocation. It does not skip or deduplicate a scheduled minute.
Waiting is not interrupted by Runner lifetime limits or graceful stop checks.
As with other synchronous handlers, background work dispatched by the handler
requires its own job-level protection.

To choose another driver or customize each lock, implement
`SchedulerLockInterface::createLock(ScheduleEvent $event): LockInterface` and
override its binding in the host application's `di.config.php`:

```php
'bindings' => [
    \Windwalker\Core\Schedule\SchedulerLockInterface::class => \App\Schedule\CustomSchedulerLock::class,
],
```

The implementation owns its Symfony `LockFactory`, store, resource namespace,
TTL and logger. Return a new, unacquired lock per call; Core manages blocking
acquisition and release. For expiring distributed stores, choose a TTL longer
than the handler runtime; the synchronous runner does not automatically renew it.
Direct PHP construction of `ScheduleService` now requires this dependency.

## PHP runner options

`SchedulerRunner::run()` accepts a `SchedulerOptions` object instead of
individual arguments. Like queue options, its typed public properties can be
set through named constructor arguments or assigned before calling the runner.

```php
use Windwalker\Core\Schedule\SchedulerOptions;

$options = new SchedulerOptions(
    lifetime: 3600,
    names: ['cleanup'],
    tags: ['maintenance'],
    timezone: 'UTC',
    once: false,
    maxRuns: 60
);
$runner->run($options);
```

`$runner->run()` creates fresh default options (unlimited lifetime/runs, all
names/tags, default timezone, resident mode). The runner snapshots options at
startup; changes to the options object during execution apply only to later runs.
CLI options and startup information are built from the same options object.

## Lifecycle events

Events live in `Windwalker\Core\Schedule\Event` and can be observed with
`$runner->on(EventClass::class, $listener)` or `$runner->subscribe($subscriber)`.
`schedule:worker` also forwards them to the application's event dispatcher.
Existing message-output events remain available.

| Event | Payload / timing |
|-------|------------------|
| `StartEvent` | `runner`, `schedule`; once per run after definitions are loaded |
| `LoopStartEvent` | `runner`, `time`, `run` (1-based); once per minute tick, including empty ticks |
| `BeforeScheduleRunEvent` | `runner`, `scheduleEvent`, `time`; only for selected due events |
| `AfterScheduleRunEvent` | Same context plus handler `result` and `duration` in seconds; success only |
| `ScheduleFailureEvent` | Same context plus `exception` and `duration`; after logging, before `catch()` |
| `LoopEndEvent` | `runner`, `time`, `run`, nullable `exception`; emitted even if the tick throws |
| `StopEvent` | `runner`, `reason`, attempted tick count `runs`, nullable `exception`; after cleanup |

```php
use Windwalker\Core\Schedule\Event\AfterScheduleRunEvent;

$runner->on(AfterScheduleRunEvent::class, static function (AfterScheduleRunEvent $event) use ($output) {
    $output->writeln(sprintf('%s completed in %.3fs', $event->scheduleEvent->getName(), $event->duration));
});
```

Payload properties are read-only observations, not handler replacement hooks.
Listeners may request shutdown with `$event->runner->stop('Custom reason.')`.
A before-run listener that requests shutdown prevents the handler from starting.
Listener exceptions propagate and stop the runner; they are not reported as
schedule-handler failures. Error-callback exceptions likewise propagate, with
the fatal exception available on `LoopEndEvent` and `StopEvent`.
