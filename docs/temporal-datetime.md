> Draft: to be merged into the official docs.

## Temporal 風格的日期時間物件

`Chronos` 繼承自 `DateTimeImmutable`，同時代表「時間點」與「顯示時區」。當你只需要其中一種概念時，
可以使用下列四個值物件，它們各自只表達一件事，並且沒有隱性的時區轉換：

| 類別 | 代表 | 有時區 | 範例 |
|---|---|---|---|
| `Instant` | 時間軸上的一個點 | 否（以 UTC 表示） | `2026-03-05T02:00:00Z` |
| `PlainDate` | 日曆上的日期 | 否 | `2026-03-05` |
| `PlainTime` | 牆上時間（時鐘讀數） | 否 | `10:30:00` |
| `PlainDateTime` | 日期加牆上時間 | 否 | `2026-03-05T10:30:00` |

這些類別位於 `Windwalker\Core\DateTime`，皆為 `final`、不可變，精度為微秒（不支援奈秒）。
PHP 不允許自訂類別實作 `DateTimeInterface`，因此它們不能傳給要求 `DateTimeInterface` 的 API，
需要時請轉換為 `Chronos` 或 `DateTimeImmutable`。

### 建立物件

```php
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\Instant;
use Windwalker\Core\DateTime\PlainDate;
use Windwalker\Core\DateTime\PlainDateTime;
use Windwalker\Core\DateTime\PlainTime;

$date = PlainDate::of(2026, 3, 5);
$time = PlainTime::of(10, 30);
$dateTime = PlainDateTime::of(2026, 3, 5, 10, 30);
$combined = PlainDateTime::combine($date, $time);

$instant = Instant::fromEpochSeconds(1772676000);
$instant = Instant::fromEpochMilliseconds(1772676000123);
```

字串解析是嚴格的，不使用 `strtotime()`，格式不符會丟出 `InvalidArgumentException`：

```php
PlainDate::parse('2026-03-05');                   // Y-m-d
PlainTime::parse('10:30:15.123456');              // H:i、H:i:s 或 H:i:s.u
PlainDateTime::parse('2026-03-05T10:30:00');
Instant::parse('2026-03-05T10:30:00+08:00');      // 必須帶時區
```

從既有的 `DateTimeInterface` 轉換使用 `from()`。`Plain*` 的 `from()` 直接取用該物件的
牆上欄位，不做時區轉換；`Instant::from()` 則取其時間點：

```php
$chronos = Chronos::create('2026-03-05 23:30:00', 'Asia/Taipei');

PlainDate::from($chronos)->toIso();   // 2026-03-05
PlainTime::from($chronos)->toIso();   // 23:30:00
Instant::from($chronos)->toIso();     // 2026-03-05T15:30:00Z
```

### 取得「現在」

統一入口是 `Clock`，它會跟隨 `Clock::set()` 設定的時鐘（例如測試時使用 `MockClock`）：

```php
use Windwalker\Core\DateTime\Clock;

Clock::instant();
Clock::plainDate('Asia/Taipei');
Clock::plainTime('Asia/Taipei');
Clock::plainDateTime('Asia/Taipei');
```

`Plain*` 的 `$tz` 參數省略時使用時鐘本身的時區。各類別也有自己的 `PlainDate::today()`、
`PlainTime::now()`、`PlainDateTime::now()`、`Instant::now()`。

### 讀取欄位

欄位同時提供兩種形式：

- **屬性**：回傳補零字串，適合直接輸出，例如 `$date->month` 為 `'03'`。
- **`get*()` 方法**：回傳整數，適合計算，例如 `$date->getMonth()` 為 `3`。

```php
$dateTime = PlainDateTime::of(2026, 3, 5, 9, 5, 7);

$dateTime->year;        // '2026'
$dateTime->month;       // '03'
$dateTime->hour;        // '09'
$dateTime->getMonth();  // 3
$dateTime->getHour();   // 9
$dateTime->getDayOfWeek();
$dateTime->getDaysInMonth();
```

`PlainDate` 與 `PlainDateTime` 有 `year`、`month`、`day`、`dayOfWeek`、`daysInMonth`；
`PlainTime` 與 `PlainDateTime` 有 `hour`、`minute`、`second`、`microsecond`；
`Instant` 有 `epochSeconds`、`microsecond`。

`PlainDate` 與 `PlainDateTime` 另有 `dayOfTheYear`（從 1 開始，即 Temporal 的 `dayOfYear`）、`dayOfYear`（從 0 開始，與 `Chronos` 一致，已 deprecated）、`weekOfYear`、`yearOfWeek`、`daysInYear`、`inLeapYear`，
對應的方法為 `getDayOfTheYear()`、`getDayOfYear()`、`getWeekOfYear()`、`getYearOfWeek()`、`getDaysInYear()`、`isInLeapYear()`。
下個 major release 將以 Temporal 版本為主：`dayOfYear` 會改為從 1 開始。

### 加減與修改欄位

`plus()`、`minus()` 接受 `Duration`、`DateInterval` 或 ISO 8601 duration 字串，
`with()` 則用具名參數替換欄位。所有操作都回傳新物件：

```php
use Windwalker\Core\DateTime\DateOverflow;

$date = PlainDate::of(2026, 1, 31);

$date->plus('P1M')->toIso();           // 2026-02-28
$date->minus('P1D')->toIso();          // 2026-01-30
$date->with(month: 2)->toIso();        // 2026-02-28
```

### 溢位處理

當結果日期不存在時，由 `DateOverflow` 決定行為：

| 值 | 行為 |
|---|---|
| `Constrain`（`plus`、`minus`、`with` 預設） | 收斂到當月最後一天，1/31 加一個月為 2/28 |
| `Reject` | 丟出 `RangeException`（`of()` 預設） |
| `Native` | 沿用 PHP 原生行為，1/31 加一個月為 3/3 |

```php
PlainDate::of(2026, 2, 30);                                   // RangeException
PlainDate::of(2026, 2, 30, DateOverflow::Constrain)->toIso(); // 2026-02-28
PlainDate::of(2026, 1, 31)->plus('P1M', DateOverflow::Native)->toIso(); // 2026-03-03
```

`PlainTime` 加減時會像時鐘一樣繞過午夜，`23:30` 加 1 小時為 `00:30`。
`Instant` 只接受以時間為單位的 duration，不能加上「月」這類需要日曆的單位。

### 比較

`compareTo()`、`equals()`、`isBefore()`、`isAfter()` 只能在相同種類之間比較，
跨種類（例如 `PlainDate` 與 `PlainTime`）會丟出 `TypeError`。
`Instant` 另外可以與任何 `DateTimeInterface` 比較時間點。

### 時間差：`until`、`since`、`diff`

四種物件都有 `until()` / `since()`，回傳 `DateInterval`（各欄位皆為非負，正負號放在 `invert`，與 PHP `diff()` 相同）。

```php
$a = PlainDate::of(2026, 1, 31);
$b = PlainDate::of(2026, 3, 1);

$a->until($b);                    // +29 天（預設 largestUnit 為 day）
$a->until($b, 'month');           // +1 月 1 天
$b->since($a);                    // $b - $a
$a->diff($b, absolute: true);     // PHP 風格，等同 until() 預設單位
```

參數：`$largestUnit`、`$smallestUnit`（`DateTimeUnit` 或 `'day'`、`'days'` 等字串）、
`$roundingIncrement`、`$roundingMode`（PHP 原生 `RoundingMode`，`TowardsZero` 即 Temporal 的 trunc，預設值）。

| 型別 | 預設 largestUnit | 可用單位 |
|---|---|---|
| `PlainDate` | day | year ~ day |
| `PlainDateTime` | day | year ~ microsecond |
| `PlainTime` | hour | hour ~ microsecond |
| `Instant` | second | hour ~ microsecond |

不支援 week，因為 `DateInterval` 沒有對應欄位。`PlainDate` / `PlainDateTime` 的月份以月份索引計算，
`2026-01-31` 到 `2026-03-01` 是 1 月 1 天。

### 取整：`round`

`PlainTime`、`PlainDateTime`、`Instant` 有 `round($smallestUnit, $roundingIncrement = 1, $roundingMode = HalfAwayFromZero)`。
`PlainDate` 沒有比日更小的單位，所以沒有 `round()`。`PlainTime` 取整後會繞過午夜。

```php
PlainTime::parse('23:59:59.6')->round('second');          // 00:00:00
PlainTime::parse('10:07:30')->round('minute', 15);        // 10:15:00
PlainDateTime::parse('2026-01-31T13:00')->round('day');   // 2026-02-01T00:00:00
```

### 轉換

要補上或換成時區，必須明確呼叫轉換方法：

```php
$date = PlainDate::of(2026, 3, 5);
$dateTime = PlainDateTime::of(2026, 3, 5, 10, 30);

$date->toPlainDateTime();                       // 00:00:00
$date->toPlainDateTime(PlainTime::of(12));
$dateTime->toPlainDate();
$dateTime->toPlainTime();

$dateTime->toChronos('Asia/Taipei');            // 把牆上時間放進指定時區
$dateTime->toDateTime('Asia/Taipei');           // 同上，回傳 DateTimeImmutable

Instant::now()->toChronos('Asia/Taipei');       // 同一個時間點，以台北時間顯示
```

`toChronos($tz)` 的 `$tz` 省略時一律為 **UTC**，不會使用應用程式的預設時區。
`PlainTime` 沒有日期，因此沒有 `toChronos()`。`Chronos` 本身也有反向的
`toInstant()`、`toPlainDate()`、`toPlainTime()`、`toPlainDateTime()`。

`Chronos::wrap()` 也可以接收 `Instant`、`PlainDate`、`PlainDateTime`，並套用上述 UTC 預設。

### 轉換函式

需要把「任意輸入」轉成某個類別時，使用全域函式。它們是嚴格的，不使用 `strtotime()`，
輸入已是目標類型時直接回傳自身：

```php
use function Windwalker\to_instant;
use function Windwalker\to_plain_date;
use function Windwalker\to_plain_datetime;
use function Windwalker\to_plain_time;
use function Windwalker\try_plain_date;

to_plain_date('2026-03-05');
to_plain_date($chronos);            // 取牆上日期
try_plain_date(null);               // null
try_plain_date('');                 // null
```

函式有 `to_instant()`、`to_plain_date()`、`to_plain_time()`、`to_plain_datetime()`，
以及對應的 `try_*()`，後者遇到 `null` 或空字串回傳 `null`。會損失資訊的轉換會丟出 `TypeError`，
例如 `to_instant()` 不接受 `Plain*`（沒有時區就沒有時間點），`to_plain_datetime()` 不接受
`PlainDate`、`PlainTime`、`Instant`。

### 換時區：`withTimezone` 與 `withTimezoneKeepingLocal`

`new Chronos($str, $tz)` 的 `$tz` 是「解讀字串所用的時區」；若字串自帶時區，`$tz` 會被忽略。
換時區時有兩種不同語意，`Chronos` 提供命名明確的方法：

```php
$taipei = Chronos::create('2026-03-05 10:00:00', 'Asia/Taipei');

$taipei->withTimezone('UTC');
// 2026-03-05 02:00:00 UTC，時間點不變，牆上時間改變（同 setTimezone()）

$taipei->withTimezoneKeepingLocal('UTC');
// 2026-03-05 10:00:00 UTC，牆上時間不變，時間點改變
```

後者在目標時區不存在該牆上時間（夏令時間跳過的時段）時，會由 PHP 往後推移。

### 在 Entity 中使用

`date` 與 `time` 欄位可以分別使用 `PlainDateCast` 與 `PlainTimeCast`，對應到
`PlainDate` 與 `PlainTime` 屬性，它們不做時區轉換：

```php
use Windwalker\Core\DateTime\PlainDate;
use Windwalker\Core\DateTime\PlainDateCast;
use Windwalker\Core\DateTime\PlainTime;
use Windwalker\Core\DateTime\PlainTimeCast;
use Windwalker\ORM\Attributes\CastNullable;
use Windwalker\ORM\Attributes\Column;

#[Column('birthday')]
#[CastNullable(PlainDateCast::class)]
public ?PlainDate $birthday = null;

#[Column('opens')]
#[CastNullable(PlainTimeCast::class)]
public ?PlainTime $opens = null;
```

讀取時 `null`、空字串與 MySQL 的 `0000-00-00` 會成為 `null`。`PlainDateCast` 接受 `Y-m-d`，
也接受後面帶時間的字串；格式不合時丟出 `InvalidArgumentException`。`PlainTimeCast` 接受
`H:i[:s[.u]]`，超過 24 小時的值（例如 MySQL 的 `838:59:59`）以及 PostgreSQL 帶時區的
`timetz` 都不支援，會丟出例外。

實體產生器遇到 `date` 欄位會自動產生 `?PlainDate` 與 `PlainDateCast`，遇到 `time` 欄位會自動
產生 `?PlainTime` 與 `PlainTimeCast`。`datetime` 欄位仍然使用 `Chronos` 與 `ServerTimeCast`。

### JSON 與字串

所有物件都實作 `JsonSerializable` 與 `Stringable`，輸出為 `toIso()` 的結果：

| 類別 | 格式 |
|---|---|
| `Instant` | `2026-03-05T02:00:00Z`，有微秒時為 `...:00.123456Z` |
| `PlainDate` | `2026-03-05` |
| `PlainTime` | `10:30:00`，有微秒時為 `10:30:00.123456` |
| `PlainDateTime` | `2026-03-05T10:30:00`，有微秒時為 `...:00.123456` |
