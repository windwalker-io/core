# Serialized task command

`task:run` runs one trusted serialized Closure or callable object with a public
`__invoke()` method. It boots through the application's normal
console entry point. It does not provide concurrency, retries or a task queue.

## Input

Specify exactly one input source:

```shell
php windwalker task:run --code="<signed-payload>"
php windwalker task:run --file="task.payload"
php windwalker task:run --stdin
```

All sources accept the same signed format, with optional surrounding whitespace:
`v1.<base64-serialized-task>.<hex-hmac-sha256>`. Files contain payload data, not
PHP source. No `eval()` or PHP file inclusion is performed. Unsigned payloads
are rejected.

Create a Closure payload from a normal PHP source file:

```php
use Windwalker\Core\Task\TaskPayload;

$siteId = 12;
$task = static fn() => ['siteId' => $siteId, 'count' => 3];
$payload = new TaskPayload($app)->encode($task);
```

The serializer uses `laravel/serializable-closure`, required by Core. Closures
need readable source files; closures created in `eval()` or `php -r` are not
supported. Capture serializable data, not containers, connections or streams.
Wrap nested closures explicitly if serializing an object containing them.

Objects need no task interface; both Closures and public `__invoke()` methods
are executed through `$app->call($task)`, allowing typed parameters to receive
services from the child application's DI container. Their classes must
be autoloadable in the child process. Services, transactions and in-memory
state from the parent are not transferred. Services are resolved in the child
process; no explicit task arguments are passed. Missing required dependencies
produce a rejected result.

### Captured data versus runtime services

`function () use ($a, $b, $c)` captures caller-supplied data for serialization
and transfer to the child. Use it for entities, value objects, parameters and
IDs. Serializable objects are supported when their classes are autoloadable
in the child and their entire object graph is serializable.

`function (A $a, B $b, C $c)` declares runtime dependencies resolved by the
child's DI container through `$app->call()`. These services are not serialized
from the parent, so non-serializable services belong here. The execution
environment is **console**, not the originating HTTP request: services must be
available in console DI, and request/authentication state is not inherited.

For example, PDO/database connections must not be captured in `use`. Capture
entities, values, parameters or IDs instead, and inject the external service:

```php
// Register PDO in the application's console DI configuration.
$result = $runner->mustRun(
    static function (\PDO $db) use ($ids, $params): array {
        $statement = $db->prepare('SELECT title FROM articles WHERE id = ? AND state = ?');
        $titles = [];

        foreach ($ids as $id) {
            $statement->execute([$id, $params['state']]);
            $titles[$id] = $statement->fetchColumn();
        }

        return $titles;
    }
);
```

An entity must be a serializable data snapshot, not an object carrying a live
connection/resource. When unsure, send IDs and reload in the child. Parent
connections and transactions are not shared. Pass required request-specific
data explicitly rather than relying on web-only services.

`TaskPayload` obtains the signing key from `$app->getSecret('task.run')`.
`AppProvider` registers it for injection into commands and application services.
The command verifies the version and HMAC using `hash_equals()` before PHP
unserialization can execute any object hooks. Modified payloads, unsigned
payloads, and payloads signed with another application's secret are rejected.
Both signing and verification require a non-empty application secret.

**Only accept trusted local payloads.** A valid signature authenticates a
holder of the shared secret; it is not a sandbox or replay protection. Signed
payloads remain executable after one use and until the signing key changes.
Protect the application secret and payload files, and never expose task
signing or execution to arbitrary HTTP input or user uploads.

## Results and output

By default the command writes one JSON result to stdout. Ordinary buffered
PHP output is forwarded to stderr. Tasks must not close the command's output
buffer or write directly to stdout in this mode.

For manual subprocess callers, `--result-file="result.json"` remains supported: stdout and stderr
remain task output, and JSON is written separately after execution. Use a
unique result path in a private directory for each invocation. Existing result
files are overwritten; the caller owns cleanup. Check the process exit code
and parse the result only after the process exits.

Success:

```json
{"version":1,"status":"fulfilled","value":{"count":3}}
```

Failure:

```json
{"version":1,"status":"rejected","error":{"class":"DomainException","message":"Invalid input","code":0}}
```

Values follow PHP's `json_encode()` semantics, including public object
properties and `JsonSerializable`. PHP object identity and class information
are not preserved. Invalid UTF-8, recursive values, resources and non-finite
numbers fail result encoding and produce a rejected result.

Exit codes are `0` for success, `1` for task/result encoding failures, and `2`
for invalid input. Result-file write failures terminate with a console error.
Process termination, fatal errors or tasks calling `exit()` may produce no
result. Callers must treat missing or malformed results as failures, not `null`.

## TaskRunner

`AppProvider` registers `Windwalker\Core\Task\TaskRunner` for service injection.
The runner checks that `windwalker/promise` is installed when constructed and
throws a `LogicException` with an installation command if it is missing.
It starts a fresh PHP CLI process immediately for each `run()` and returns a
`Windwalker\Promise\Promise`:

```php
use Windwalker\Core\Task\TaskRunner;
use Windwalker\Promise\Promise;

$runner = $app->retrieve(TaskRunner::class);

$result = $runner->run(static fn() => ['count' => 3])->wait();
$value = $result->value;
$stdout = $result->output;
$stderr = $result->errorOutput;
$process = $result->process;

$results = Promise::all([
    'first' => $runner->run(static fn() => ['count' => 3]),
    'second' => $runner->run(new BuildStatsTask()),
])->wait();

// Synchronous batch; returns settled results directly.
$results = $runner->mustRunMultiple([
    'first' => static fn() => ['count' => 3],
    'second' => new BuildStatsTask(),
]);

// Wait for every task, retaining both successes and failures.
$results = $runner->runMultiple([
    'first' => static fn() => ['count' => 3],
    'second' => new BuildStatsTask(),
])->wait();
```

`runMultiple()` resolves to an array of
`Windwalker\Promise\SettledResult` objects with the same keys and input order.
Each result's `status` is `PromiseState::FULFILLED` or `PromiseState::REJECTED`;
`value` contains a `TaskResult` or rejection exception, respectively.
`mustRunMultiple()` calls `runMultiple()->wait()` and returns the same settled
results directly. Individual failures do not throw or cancel siblings; inspect
their status and decide how to proceed. Empty batches return/resolve to `[]`.

`mustRun()` calls `run()->wait()` and returns `TaskResult` directly. It unwraps
Promise's `UncaughtException`, so callers can catch the underlying task error:

```php
try {
    $result = $runner->mustRun(new BuildStatsTask());
} catch (\Windwalker\Core\Task\TaskExecutionException $e) {
    // Handle the task failure.
}
```

For fail-on-any aggregation, explicitly compose `Promise::all()` with `run()`.

Closures must still have readable source
files and objects must be autoloadable by the child. The host must provide its
normal `@root/windwalker` entry point and a shared, writable result transport.

The runner signs input, including a random task ID and result TTL, sends it
through stdin, and invokes `task:run --stdin --ipc`. The command publishes its
JSON-compatible envelope through `TaskResultTransportInterface` without
redirecting stdout/stderr. Results retain the JSON semantics described above.
The runner reads results only after process termination, validates the task ID
and protocol, then deletes the entry. No per-task temporary result files are
created by the runner. A missing/evicted result is a failure, even with exit code 0.

Successful promises resolve to `TaskResult`: `value`, `taskId`, `name`,
`process`, `captureOutput`, and process-backed `output`, `errorOutput`, `exitCode`.
The original Symfony `Process` remains available for diagnostics. Full stdout
and stderr are retained by default; limit batch sizes and output volumes.

```php
use Windwalker\Core\Task\TaskRunnerOptions;

$results = $runner->mustRunMultiple(
    ['report' => new BuildStatsTask()],
    new TaskRunnerOptions(
        onOutput: static function (string|int|null $name, string $type, string $chunk): void {
            // $type is Symfony Process::OUT or Process::ERR.
            echo $name . ': ' . $chunk;
        },
        captureOutput: false,
        resultTtl: 300
    )
);
```

`onOutput` runs during scheduler polling and receives each task's original
batch key, stream type and chunk. For single `run()`, set the options' `name` or receive
`null`. `captureOutput: false` still delivers chunks, then clears process
buffers; result output/errorOutput and failure stderr are empty. Streaming
does not start an independent parent event loop. Callback exceptions stop and
reject the affected task; siblings continue.

### Result transport and DI

The default `TaskResultTransportInterface` factory creates a private PSR-6 pool
for `CacheTaskResultTransport`, using `FileStorage` under `@cache/task-results`,
separate from normal application cache-disable settings. The pool is not
registered in DI and is never created when the transport binding is overridden.
Bind `TaskResultTransportInterface` in an application
provider/configuration loaded by **both parent and child**:

```php
use Windwalker\Core\Task\CacheTaskResultTransport;
use Windwalker\Core\Task\TaskResultTransportInterface;

$container->share(
    TaskResultTransportInterface::class,
    static fn() => new CacheTaskResultTransport($sharedPool)
);
```

The pool must share storage across processes; array/null pools are unsuitable.
Use a private, trusted namespace, not untrusted cache input. For custom IPC,
implement `publish(string $taskId, array $result, int $ttl): void`,
`receive(string $taskId): ?array`, and `delete(string $taskId): void`, or use
the callback adapter:

```php
use Windwalker\Core\Task\CallbackTaskResultTransport;

$transport = new CallbackTaskResultTransport(
    publish: static function (string $id, array $result, int $ttl) use ($channel): void {
        $channel->publish($id, $result, $ttl);
    },
    receive: static fn(string $id): ?array => $channel->receive($id),
    delete: static function (string $id) use ($channel): void {
        $channel->delete($id);
    }
);
```

Adapters must throw on storage failures; `receive()` returns `null` only for
missing/expired data. Failed PSR-6 writes/deletes raise explicit errors.
A runtime-only parent binding/instance is not transferred to the child.

`resultTtl` defaults to 300 seconds, must be positive, and begins when the child
publishes, not at process start. It is independent of the process timeout.
Normal completion deletes entries; parent crashes can leave results until TTL
expiration. TTL means logical expiration, not guaranteed immediate physical
pruning: configure backend pruning/maintenance when needed. Cache eviction can
discard results early. This is transient IPC, not durable delivery.

Manual IPC callers use `TaskPayload::encodeRequest($task, $taskId, $ttl)` with a
32-character lowercase hexadecimal task ID and `--ipc`; task ID and TTL are
authenticated before deserialization. `--ipc` cannot combine with `--result-file`.
Legacy `encode()` and manual stdout/result-file modes remain unchanged.

`run()`, `mustRun()`, `mustRunMultiple()` and `runMultiple()` accept a `TaskRunnerOptions`
object as their second argument. Its defaults are `timeout: 60`, `onOutput: null`,
`captureOutput: true`, `name: null`, and `resultTtl: 300`. Batch keys override
`name` per child without modifying the supplied options.
For example, `run($task, new TaskRunnerOptions(timeout: 10))` sets a per-process
timeout in seconds, measured from process start. Set `timeout: null` to disable it.
Zero, negative and non-finite timeouts are rejected. Timeout errors terminate
the process and reject with `TaskExecutionException`, whose `getPrevious()`
is Symfony's `ProcessTimedOutException`.

Task failures reject with `TaskExecutionException`, exposing the remote class,
message, code, process exit code, retained stderr, `process`, `taskId` and `name`.
Transport, protocol and output-callback failures also expose the process with
the underlying exception in `getPrevious()`. Startup and serialization errors
reject with their original exception. Cleanup errors reject explicitly.
`catch()` can handle these reasons
directly; Windwalker's `wait()` wraps an unhandled rejection in
`UncaughtException` (use `getReason()` for the underlying error).

Execution is parallel in child processes, not in the parent PHP code. The
Promise scheduler drives a shared polling loop when waiting; with the default
deferred scheduler, a wait drains all tasks already submitted to that runner.
Completed tasks settle as they are observed, even if a sibling is slower.
An aggregate rejection does not cancel siblings: they finish (or time out)
and are cleaned up before an unhandled rejection escapes the polling loop.
`then()` and `catch()` are supported. This initial runner starts every submitted
task immediately, with no concurrency cap, retries, cancellation API, or
resident worker pool. Submit bounded batches.
