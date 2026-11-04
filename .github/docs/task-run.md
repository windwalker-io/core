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

For subprocess callers, use `--result-file="result.json"`: stdout and stderr
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
