<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Auth\Method;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Windwalker\Authentication\AuthResult;
use Windwalker\Core\Auth\Method\DatabaseMethod;
use Windwalker\Core\Runtime\Config;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Core\Testing\Traits\InMemoryDatabaseTestTrait;

#[CoversClass(DatabaseMethod::class)]
class DatabaseMethodTest extends TestCase
{
    use InMemoryDatabaseTestTrait;

    protected function prepareInMemoryDatabaseSchema(DatabaseAdapter $db): void
    {
        $db->execute(
            <<<'SQL'
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL
            )
            SQL
        );

        $db->getWriter()->insertOne(
            'users',
            [
                'username' => 'foo',
                'email' => 'foo@example.com',
                'password' => password_hash('secret', PASSWORD_DEFAULT),
            ],
            'id'
        );

        $db->getWriter()->insertOne(
            'users',
            [
                'username' => 'weak',
                'email' => 'weak@example.com',
                'password' => password_hash('secret', PASSWORD_DEFAULT, ['cost' => 4]),
            ],
            'id'
        );
    }

    private function createMethod(array $options = []): DatabaseMethod
    {
        return new DatabaseMethod(
            $this->db,
            new Config(),
            array_merge(['table' => 'users'], $options)
        );
    }

    #[Test]
    public function constructThrowsExceptionWhenTableOptionIsMissing(): void
    {
        $this->expectException(MissingOptionsException::class);

        new DatabaseMethod($this->db, new Config());
    }

    #[Test]
    public function authenticateReturnsEmptyCredentialWhenUsernameIsMissing(): void
    {
        $method = $this->createMethod();

        $result = $method->authenticate(['password' => 'secret']);

        self::assertSame(AuthResult::EMPTY_CREDENTIAL, $result->getStatus());
    }

    #[Test]
    public function authenticateReturnsEmptyCredentialWhenPasswordIsEmpty(): void
    {
        $method = $this->createMethod();

        $result = $method->authenticate(['username' => 'foo', 'password' => '']);

        self::assertSame(AuthResult::EMPTY_CREDENTIAL, $result->getStatus());
    }

    #[Test]
    public function authenticateReturnsUserNotFoundWhenNoMatchingRow(): void
    {
        $method = $this->createMethod();

        $result = $method->authenticate(['username' => 'nobody', 'password' => 'secret']);

        self::assertSame(AuthResult::USER_NOT_FOUND, $result->getStatus());
    }

    #[Test]
    public function authenticateReturnsInvalidPasswordWhenPasswordDoesNotMatch(): void
    {
        $method = $this->createMethod();

        $result = $method->authenticate(['username' => 'foo', 'password' => 'wrong']);

        self::assertSame(AuthResult::INVALID_PASSWORD, $result->getStatus());
    }

    #[Test]
    public function authenticateReturnsSuccessAndMergesUserDataWithoutRehash(): void
    {
        $method = $this->createMethod();

        $result = $method->authenticate(['username' => 'foo', 'password' => 'secret']);

        self::assertSame(AuthResult::SUCCESS, $result->getStatus());
        self::assertSame('foo@example.com', $result->getCredential()['email']);

        $row = $this->db->select('*')->from('users')->where('username', 'foo')->get();

        self::assertTrue(password_verify('secret', $row->password));
    }

    #[Test]
    public function authenticateRehashesWeakPasswordOnSuccess(): void
    {
        $method = $this->createMethod();

        $before = $this->db->select('*')->from('users')->where('username', 'weak')->get();
        self::assertTrue(password_needs_rehash($before->password, PASSWORD_DEFAULT));

        $result = $method->authenticate(['username' => 'weak', 'password' => 'secret']);

        self::assertSame(AuthResult::SUCCESS, $result->getStatus());

        $after = $this->db->select('*')->from('users')->where('username', 'weak')->get();

        self::assertNotSame($before->password, $after->password);
        self::assertTrue(password_verify('secret', $after->password));
        self::assertFalse(password_needs_rehash($after->password, PASSWORD_DEFAULT));
    }

    #[Test]
    public function authenticateMatchesAnyConfiguredLoginNameField(): void
    {
        $method = $this->createMethod(['login_name' => ['username', 'email']]);

        $result = $method->authenticate(['email' => 'foo@example.com', 'password' => 'secret']);

        self::assertSame(AuthResult::SUCCESS, $result->getStatus());
    }
}
