> Draft: to be merged into the official docs.

## 在子程序中執行 Task

`TaskRunner` 將 Closure 或具有公開 `__invoke()` 方法的物件送到獨立 PHP CLI
子程序執行，並以 Promise 取得結構化回傳資料。適合把彼此獨立的計算或查詢並行處理，
或將一次性工作隔離於目前程序；它不是持久化 queue，也不提供失敗重試。

### 執行單一工作

以下範例放在應用程式的 PHP 原始檔內執行，假設 `$app` 是已啟動的 application。
不要在 `php -r`、REPL 或 `eval()` 中建立要序列化的 Closure。

```php
use Windwalker\Core\Task\TaskRunner;

$runner = $app->retrieve(TaskRunner::class);
$numbers = [1, 2, 3];

$promise = $runner->run(
    static fn(): array => ['total' => array_sum($numbers)]
);

$result = $promise->wait();
$value = $result->value;
// ['total' => 6]
```

`run()` 呼叫時便啟動子程序，回傳 `Windwalker\Promise\Promise`，不必等到
`wait()` 才開始工作。也可以透過 `then()` 處理成功結果：

```php
use Windwalker\Core\Task\TaskResult;

$total = $runner->run(static fn(): int => 6)
    ->then(static fn(TaskResult $result): int => $result->value * 2)
    ->wait();
// 12
```

Closure 捕捉的資料必須可序列化，避免捕捉 container、資料庫連線、stream 或
整個 `$this`。父程序中的記憶體變更與 transaction 不會轉移到子程序。

### 注入 Service 與使用物件工作

Closure 與物件的 `__invoke()` 都透過子程序的 `$app->call($task)` 執行，
可在參數宣告需要的 service。以下範例注入既有的 application service：

Task Closure 有兩種不同的輸入方式：

- `function () use ($a, $b, $c)`：使用者從父程序帶入的資料，會隨 Closure
  序列化後傳給子程序。支援可序列化物件，包括 entity、value object，以及
  params、IDs 等資料；物件類別必須能在子程序 autoload，且整個物件圖都必須可序列化。
- `function (A $a, B $b, C $c)`：執行期由子程序 DI container 注入的 service，
  不會從父程序序列化傳送。無法序列化的外部 service 應透過這裡取得，
  但必須在子程序的 **console 環境**可解析。

例如 DB／PDO 連線不能放在 `use`；應捕捉 entity、value、params 或 IDs，
再由參數注入資料庫或其他外部 service 處理工作：

```php
// $ids and $params are serializable data supplied by the caller.
// PDO must be registered in the application's console DI configuration.
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

不要捕捉 `$db`、`$pdo`、container 或持有連線的 service。Entity 也必須是可序列化
的資料快照；若內含連線、resource 或不可序列化的關聯狀態，改傳 ID 並在子程序重新查詢。
父子程序不共用同一條連線或 transaction。參數注入機制與一般 `$app->call()` 相同，
差別在於這裡使用 console application 的 service 與生命週期，不能假設存在目前 HTTP
request、登入狀態或其他僅限 web request 的服務；需要的資料應明確透過 `use` 傳入。

```php
use Windwalker\Core\Application\ApplicationInterface;

$result = $runner->run(
    static fn(ApplicationInterface $app): array => ['name' => $app->getAppName()]
)->wait();
```

也可以使用不需實作介面的 invokable object。將類別存為
`src/Task/SumTask.php`，並確保子程序可以 autoload：

```php
<?php

declare(strict_types=1);

namespace App\Task;

use Windwalker\Core\Application\ApplicationInterface;

class SumTask
{
    public function __construct(private array $numbers) {}

    public function __invoke(ApplicationInterface $app): array
    {
        return [
            'application' => $app->getAppName(),
            'total' => array_sum($this->numbers),
        ];
    }
}
```

```php
use App\Task\SumTask;

$result = $runner->run(new SumTask([1, 2, 3]))->wait();
```

Service 必須由子程序的 DI container 提供；Runner 不會傳送父程序已建立的 service
instance，也不傳入額外的 callback arguments。必要參數無法解析時，工作會失敗。

### 並行與批次執行

分別提交工作，再交給 `Promise::all()` 聚合：

```php
use Windwalker\Promise\Promise;

$results = Promise::all([
    'sum' => $runner->run(static fn(): int => array_sum([1, 2, 3])),
    'count' => $runner->run(static fn(): int => count([1, 2, 3])),
])->wait();
$sum = $results['sum']->value; // 6
$count = $results['count']->value; // 3
```

`TaskRunner::mustRunMultiple()` 直接傳入 task，同步等待並回傳所有 settled results：

```php
$results = $runner->mustRunMultiple([
    'sum' => static fn(): int => array_sum([1, 2, 3]),
    'count' => static fn(): int => count([1, 2, 3]),
]);
```

兩者保留輸入 key 與順序，不依工作完成順序排列。`Promise::all()` 在任一工作失敗時
reject；`mustRunMultiple()` 則保留每筆成功與失敗，由使用者決定是否放棄。
兩者都不會取消其他已啟動的工作。

若希望個別失敗不影響取得其他結果，使用 `runMultiple()`：

```php
use Windwalker\Promise\Enum\PromiseState;

$results = $runner->runMultiple([
    'sum' => static fn(): int => 6,
    'failed' => static function (): never {
        throw new \DomainException('Unable to calculate.');
    },
])->wait();

$sum = $results['sum']->value->value;
// 6

if ($results['failed']->status === PromiseState::REJECTED) {
    $message = $results['failed']->value->getMessage();
    // 'Unable to calculate.'
}
```

每筆結果是 `Windwalker\Promise\SettledResult`：

| 屬性 | 成功 | 失敗 |
|---|---|---|
| `status` | `PromiseState::FULFILLED` | `PromiseState::REJECTED` |
| `value` | `TaskResult`（其 `value` 為工作回傳值） | rejection exception |

`mustRunMultiple()` 等同 `runMultiple()->wait()`，直接回傳相同的 settled results。
個別工作失敗不會拋出例外；請檢查 `status`。空批次回傳／resolve 成 `[]`。

單筆同步執行使用 `mustRun()`，直接回傳 `TaskResult`，失敗時直接拋出原始
rejection exception，不再包裝成 `UncaughtException`：

```php
use Windwalker\Core\Task\TaskExecutionException;

try {
    $result = $runner->mustRun(new SumTask([1, 2, 3]));
    $total = $result->value['total'];
} catch (TaskExecutionException $e) {
    $message = $e->getMessage();
}
```

### Timeout 與錯誤處理

`run()`、`mustRun()`、`mustRunMultiple()` 與 `runMultiple()` 的第二個參數皆為
`TaskRunnerOptions`。所有 task 以外的參數都放在此物件，預設值為
`timeout: 60`、`onOutput: null`、`captureOutput: true`、`name: null`、
`resultTtl: 300`。批次工作以輸入 key 作為各筆 name，不修改原 options。
Timeout 單位為秒，從各子程序啟動時計算，不是整批工作的總時間。

```php
use Windwalker\Core\Task\TaskRunnerOptions;

$result = $runner->run(static fn(): int => 6, new TaskRunnerOptions(timeout: 10))->wait();
$results = $runner->mustRunMultiple(['sum' => static fn(): int => 6], new TaskRunnerOptions(timeout: 30));
$results = $runner->runMultiple(['sum' => static fn(): int => 6], new TaskRunnerOptions(timeout: null))->wait();
```

`null` 表示不限制時間。`0`、負數與非有限數值會被拒絕。
超時會終止子程序，並以 `TaskExecutionException` reject；
其 `getPrevious()` 為 Symfony 的 `ProcessTimedOutException`。

工作拋出的例外以 `TaskExecutionException` 回傳，而不是還原原始 exception instance。
可讀取 `remoteClass`、`exitCode`、`stderr`、`process`、`taskId`、`name`，
以及標準的 `getMessage()`、`getCode()`。Transport、結果格式與 output callback
錯誤也會包裝成此例外，原始錯誤放在 `getPrevious()`。
序列化與啟動程序失敗則以原始例外 reject；清理結果失敗也會明確 reject。

```php
use Windwalker\Promise\Exception\UncaughtException;

try {
    $runner->run(
        static function (): never {
            throw new \DomainException('Unable to calculate.');
        }
    )->wait();
} catch (UncaughtException $e) {
    $reason = $e->getReason();
    $message = $reason->getMessage();
}
```

`wait()` 會以 `UncaughtException` 包裝未處理的 rejection；使用 `catch()` handler
時則直接收到 rejection reason，可回傳替代值進行 recovery。

### 回傳資料與執行環境

回傳值使用 JSON 傳遞：支援 scalar、`null`、陣列、公開物件屬性與
`JsonSerializable`。父程序以 associative array 解碼 JSON object，不保留 PHP
物件類別與 identity。非法 UTF-8、循環參照、resource、`INF` 或 `NAN` 會導致失敗。

Runner 使用應用程式的 `@root/windwalker` 啟動 PHP CLI，透過 stdin 傳送簽章
request，並以 `--ipc` 將結果寫入專用 transport，不再建立每筆工作的暫存結果檔。
工作 ID 與 TTL 都包含在簽章內。父程序在子程序結束後才讀取結果，驗證 ID 與版本，
隨後刪除；即使 exit code 為 `0`，缺少結果也會 reject，不能視為成功的 `null`。

成功結果是 `TaskResult`，提供 `value`、`taskId`、`name`、`process`、
`captureOutput`，以及直接讀取 Symfony Process 的 `output`、`errorOutput`、
`exitCode`。stdout/stderr 不會混入結果資料，預設完整保留，請自行限制輸出量。

### 輸出 streaming

```php
$results = $runner->mustRunMultiple(
    ['sum' => new SumTask([1, 2, 3])],
    new TaskRunnerOptions(
        onOutput: static function (string|int|null $name, string $type, string $chunk): void {
            echo $name . ': ' . $chunk;
        },
        captureOutput: false
    )
);
```

`$name` 是原始 task key，`$type` 是 Symfony `Process::OUT` 或 `Process::ERR`。
單筆 `run()` 可設定 options 的 `name`，否則為 `null`。Callback 在 Promise polling 時觸發，
不是獨立的父程序 event loop。`captureOutput: false` 仍會送出 chunk，之後清除
process buffers；結果的 output/errorOutput 與失敗例外的 stderr 為空字串。
Callback 拋出例外時只停止並 reject 該筆工作，不會取消其他工作。

### 自訂結果通道

預設 `TaskResultTransportInterface` factory 內部建立專用 PSR-6 cache pool，
供 `CacheTaskResultTransport` 使用，儲存在 `@cache/task-results`。
Pool 不另行註冊到 DI；覆蓋 transport binding 後就不會建立預設 pool。
不受一般 cache disabled 設定影響；使用預設 transport 時，該路徑必須由父子程序共用且可寫。

可在應用程式 provider 中抽換 `TaskResultTransportInterface`：

```php
use Windwalker\Core\Task\CacheTaskResultTransport;
use Windwalker\Core\Task\TaskResultTransportInterface;

$container->share(
    TaskResultTransportInterface::class,
    static fn() => new CacheTaskResultTransport($sharedPool)
);
```

這份 binding 必須在父、子程序啟動時都載入；只替換父程序的 runtime instance 不會
傳入子程序。Cache 必須能跨程序共享，不能用記憶體 array 或 null pool。

也可以實作 `TaskResultTransportInterface`，或傳入 callback adapter：

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

`receive()` 只在結果不存在或過期時回傳 `null`，儲存失敗應拋出例外。
結果儲存區必須可信且隔離，不能接受不可信來源寫入。

`run()`、`mustRunMultiple()`、`runMultiple()` 皆可在 options 設定 `resultTtl: 300`，必須大於零，
從子程序 publish 結果時開始計算，與 timeout 獨立。正常讀取後立即刪除；
父程序意外中斷時交由 TTL 過期。TTL 不代表立即實體刪檔，仍需依 cache backend
配置 prune／維護；cache eviction 也可能提早遺失結果。這不是持久化訊息佇列。

Runner 建構時會檢查 `windwalker/promise` 是否存在；缺少時拋出 `LogicException`，
提示執行 `composer require windwalker/promise`。

使用預設 deferred scheduler 時，`wait()` 會推進並等待同一 Runner 已提交的工作。
即使某筆工作先失敗，其他工作仍會完成或超時，並在未處理的聚合錯誤離開 polling loop
前完成清理。這是子程序平行運算，不是父程序的完整非阻塞 event loop。
目前每個 task 都立即啟動新程序，沒有 concurrency 上限、取消 API 或常駐 worker pool；
應自行限制每批工作數量。

### 直接使用 `task:run`

需要自行控制 subprocess 時，可透過 `TaskPayload` 產生簽章輸入：

```php
use Windwalker\Core\Task\TaskPayload;

$payload = $app->retrieve(TaskPayload::class)->encode(static fn(): array => ['total' => 6]);
file_put_contents($app->path('@temp/sum.payload'), $payload);
```

```shell
php windwalker task:run --file="path-to-sum.payload" --result-file="result.json"
php windwalker task:run --code="<signed-payload>"
php windwalker task:run --stdin
```

`--code/-c`、`--file/-f`、`--stdin` 必須擇一。檔案內容是 payload，不是 PHP 原始碼。
預設 stdout 輸出一筆 JSON，普通 buffered PHP 輸出轉送到 stderr；task 不應直接寫入
stdout 或關閉 command 的 output buffer。使用 `--result-file` 可完全分離結果與
stdout/stderr，既有結果檔會被覆寫，呼叫端負責路徑隔離與清理。

Runner 使用的 `--ipc` 模式則保留 stdout/stderr，將結果交給 transport。
自行使用此模式時，以 `TaskPayload::encodeRequest($task, $taskId, $ttl)` 建立
request；ID 必須為 32 字元小寫十六進位字串。`--ipc` 不可與 `--result-file` 同用。

成功結果為 `{"version":1,"status":"fulfilled","value":{"total":6}}`；
失敗結果包含 `version`、`status: "rejected"` 與 `error.class/message/code`。
Exit code `0` 表示成功，`1` 表示執行或結果編碼失敗，`2` 表示輸入無效。
task 呼叫 `exit()`、fatal error 或程序被終止時可能沒有結果，不能視為成功回傳 `null`。

輸入格式為 `v1.<base64-serialized-task>.<hex-hmac-sha256>`，使用
`$app->getSecret('task.run')` 簽署，在反序列化之前驗證簽章。未簽章、被修改或使用
不同 secret 的 payload 會被拒絕，應用程式也必須設定非空 secret。
簽章只驗證持有共用 secret 的來源，不提供 sandbox 或防重放保護；不要對不可信任的
HTTP 輸入或使用者上傳內容提供簽章及執行入口。
