<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\PlainDate;
use Windwalker\Core\DateTime\PlainDateCast;
use Windwalker\Database\DatabaseFactory;
use Windwalker\ORM\Attributes\AutoIncrement;
use Windwalker\ORM\Attributes\CastNullable;
use Windwalker\ORM\Attributes\Column;
use Windwalker\ORM\Attributes\PK;
use Windwalker\ORM\Attributes\Table;
use Windwalker\ORM\ORM;

#[CoversClass(PlainDateCast::class)]
class PlainDateCastTest extends TestCase
{
    #[Test]
    public function hydrate(): void
    {
        $cast = new PlainDateCast();

        self::assertNull($cast->hydrate(null));
        self::assertNull($cast->hydrate(''));
        self::assertNull($cast->hydrate('0000-00-00'));
        self::assertSame('2026-03-05', $cast->hydrate('2026-03-05')->toIso());
        self::assertSame('2026-03-05', $cast->hydrate('2026-03-05 10:20:30')->toIso());
        self::assertSame('2026-03-05', $cast->hydrate(Chronos::create('2026-03-05 03:00', 'Asia/Taipei'))->toIso());

        $date = PlainDate::of(2026, 3, 5);
        self::assertSame($date, $cast->hydrate($date));
    }

    #[Test]
    public function hydrateRejectsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PlainDateCast()->hydrate('tomorrow');
    }

    #[Test]
    public function extract(): void
    {
        $cast = new PlainDateCast();

        self::assertNull($cast->extract(null));
        self::assertSame('2026-03-05', $cast->extract(PlainDate::of(2026, 3, 5)));
        self::assertSame('2026-03-05', $cast->extract('2026-03-05'));
        self::assertSame('2026-03-05', $cast->extract(Chronos::create('2026-03-05 03:00', 'Asia/Taipei')));
    }

    #[Test]
    public function ormRoundTrip(): void
    {
        $db = new DatabaseFactory()->create('pdo_sqlite', ['dsn' => 'sqlite::memory:']);
        $db->execute('CREATE TABLE plain_date_cast_items (id INTEGER PRIMARY KEY AUTOINCREMENT, birthday DATE NULL)');

        $orm = new ORM($db);
        $mapper = $orm->mapper(PlainDateCastItem::class);

        $item = new PlainDateCastItem();
        $item->birthday = PlainDate::of(2026, 3, 5);
        $saved = $mapper->createOne($item);

        self::assertSame(
            '2026-03-05',
            $db->prepare('SELECT birthday FROM plain_date_cast_items WHERE id = 1')->get()->birthday
        );

        $loaded = $mapper->findOne(['id' => $saved->id]);
        self::assertInstanceOf(PlainDate::class, $loaded->birthday);
        self::assertSame('2026-03-05', $loaded->birthday->toIso());

        $loaded->birthday = $loaded->birthday->plus('P1M');
        $mapper->updateOne($loaded);

        self::assertSame('2026-04-05', $mapper->findOne(['id' => $saved->id])->birthday->toIso());

        $loaded->birthday = null;
        $mapper->updateOne($loaded);

        self::assertNull($mapper->findOne(['id' => $saved->id])->birthday);
    }
}

#[Table('plain_date_cast_items')]
class PlainDateCastItem
{
    #[Column('id'), PK, AutoIncrement]
    public ?int $id = null;

    #[Column('birthday')]
    #[CastNullable(PlainDateCast::class)]
    public ?PlainDate $birthday = null;
}
