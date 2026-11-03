<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Auth;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Database\Schema\Schema;
use Windwalker\Core\Testing\Traits\DatabaseTestTrait;
use Windwalker\Core\Testing\Traits\ServiceAwareTestTrait;

class RealUserDbTest extends TestCase
{
    use ServiceAwareTestTrait;
    use DatabaseTestTrait;

    protected static function prepareTablesBeforeClass(): void
    {
        $table = static::$db->getTableManager('users');
        $table->create(
            function (Schema $schema) {
                $schema->primary('id');
                $schema->varchar('username');
                $schema->varchar('password');
                $schema->varchar('email');
            },
        );
    }

    #[Before]
    public function prepareData(): void
    {
        self::clearTables();

        static::$db->getWriter()->insertOne(
            'users',
            [
                'username' => 'admin',
                'password' => password_hash('admin', PASSWORD_DEFAULT),
                'email' => 'admin@example.com',
            ],
        );
    }

    #[Test]
    public function checkExampleUserExists(): void
    {
        $user = static::$db->select()
            ->from('users')
            ->where('username', 'admin')
            ->get();

        self::assertNotNull($user);
        self::assertSame('admin', $user->username);
    }
}
