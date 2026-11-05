<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DateTime;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\DateTime\PlainTime;
use Windwalker\Core\DateTime\PlainTimeCast;
use Windwalker\Database\DatabaseFactory;
use Windwalker\ORM\Attributes\AutoIncrement;
use Windwalker\ORM\Attributes\CastNullable;
use Windwalker\ORM\Attributes\Column;
use Windwalker\ORM\Attributes\PK;
use Windwalker\ORM\Attributes\Table;
use Windwalker\ORM\ORM;

#[CoversClass(PlainTimeCast::class)]
class PlainTimeCastTest extends TestCase
{
    #[Test]
    public function hydrateAndExtract(): void
    {
        $cast = new PlainTimeCast();

        self::assertNull($cast->hydrate(null));
        self::assertNull($cast->hydrate(''));
        self::assertSame('10:20:30', $cast->hydrate('10:20:30')->toIso());
        self::assertSame('10:20:00', $cast->hydrate('10:20')->toIso());
        self::assertSame('10:20:30.123456', $cast->hydrate('10:20:30.123456')->toIso());
        self::assertSame('03:00:00', $cast->hydrate(Chronos::create('2026-03-05 03:00'))->toIso());

        self::assertNull($cast->extract(null));
        self::assertSame('10:20:30', $cast->extract(PlainTime::of(10, 20, 30)));
        self::assertSame('10:20:30', $cast->extract('10:20:30'));
    }

    #[Test]
    public function hydrateRejectsOutOfRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PlainTimeCast()->hydrate('838:59:59');
    }

    #[Test]
    public function ormRoundTrip(): void
    {
        $db = new DatabaseFactory()->create('pdo_sqlite', ['dsn' => 'sqlite::memory:']);
        $db->execute('CREATE TABLE plain_time_cast_items (id INTEGER PRIMARY KEY AUTOINCREMENT, opens TIME NULL)');

        $mapper = new ORM($db)->mapper(PlainTimeCastItem::class);

        $item = new PlainTimeCastItem();
        $item->opens = PlainTime::of(9, 30);
        $saved = $mapper->createOne($item);

        self::assertSame(
            '09:30:00',
            $db->prepare('SELECT opens FROM plain_time_cast_items WHERE id = 1')->get()->opens
        );

        $loaded = $mapper->findOne(['id' => $saved->id]);
        self::assertSame('09:30:00', $loaded->opens->toIso());

        $loaded->opens = null;
        $mapper->updateOne($loaded);

        self::assertNull($mapper->findOne(['id' => $saved->id])->opens);
    }
}

#[Table('plain_time_cast_items')]
class PlainTimeCastItem
{
    #[Column('id'), PK, AutoIncrement]
    public ?int $id = null;

    #[Column('opens')]
    #[CastNullable(PlainTimeCast::class)]
    public ?PlainTime $opens = null;
}
