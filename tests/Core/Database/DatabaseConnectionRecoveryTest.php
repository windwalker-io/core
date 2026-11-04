<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Database;

use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use Windwalker\Core\Database\DatabaseConnectionRecovery;
use Composer\Autoload\ClassLoader;
use Symfony\Component\Process\Process;
use Windwalker\Database\Exception\DatabaseException;

#[CoversClass(DatabaseConnectionRecovery::class)]
class DatabaseConnectionRecoveryTest extends TestCase
{
    public static function connectionFailures(): iterable
    {
        foreach ([
            'SQLSTATE[HY000] [2002] Connection timed out',
            'SQLSTATE[HY000] [2006] MySQL server has gone away',
            'Lost connection to MySQL server during query',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'SQLSTATE[08006] connection failure',
            'SQLSTATE[08S01] Communication link failure',
            'SQLSTATE[57P01] database system is shutting down',
            'Connection refused',
            'Connection reset by peer',
            'Broken pipe',
            'Unexpected EOF on client connection',
        ] as $message) {
            yield $message => [new PDOException($message)];
        }

        yield 'wrapped exception' => [
            new RuntimeException('Query execution failed', previous: new PDOException('MySQL server has gone away')),
        ];
        if (class_exists(\mysqli_sql_exception::class)) {
            yield 'native MySQL code' => [new \mysqli_sql_exception('Disconnected', 2013)];
        }
        $pdo = new PDOException('Disconnected');
        $pdo->errorInfo = ['HY000', 2006, 'Disconnected'];
        yield 'PDO error info' => [$pdo];
        yield 'framework database exception' => [new DatabaseException('connection timed out')];
        yield 'MySQL-prefixed error' => [new RuntimeException('Mysql: server gone away')];
        $pdoState = new PDOException('Connection failure');
        $pdoState->errorInfo = ['08006', 7, 'Connection failure'];
        yield 'PDO SQLSTATE without message marker' => [$pdoState];
    }

    #[Test]
    #[DataProvider('connectionFailures')]
    public function recognizesDisconnectedDatabases(Throwable $exception): void
    {
        self::assertTrue(DatabaseConnectionRecovery::isConnectionFailure($exception));
    }

    public static function otherFailures(): iterable
    {
        yield 'HTTP timeout' => [new RuntimeException('Connection timed out')];
        yield 'arbitrary error code' => [new RuntimeException('Other failure', 2002)];
        yield 'SQL syntax error' => [new PDOException('SQLSTATE[42000]: Syntax error')];
        yield 'duplicate key' => [new PDOException('SQLSTATE[23000]: Duplicate entry')];
        yield 'deadlock' => [new PDOException('SQLSTATE[40001]: Deadlock found')];
        yield 'invalid credentials' => [new PDOException('SQLSTATE[28000] [1045] Access denied')];
        yield 'connection rejection' => [new PDOException('SQLSTATE[08004] authentication failed')];
        yield 'connection keyword in failed SQL' => [new PDOException("SQLSTATE[42000]: Syntax error near 'gone away'")];
    }

    #[Test]
    #[DataProvider('otherFailures')]
    public function doesNotTreatOtherErrorsAsLostConnections(Throwable $exception): void
    {
        self::assertFalse(DatabaseConnectionRecovery::isConnectionFailure($exception));
    }

    #[Test]
    public function shutdownExitsWithFailureWithoutContinuingOrDisconnectingAdapters(): void
    {
        $autoload = dirname(new \ReflectionClass(ClassLoader::class)->getFileName(), 2) . '/autoload.php';
        $code = 'require ' . var_export($autoload, true) . ';'
            . '(new \Windwalker\Core\Database\DatabaseConnectionRecovery())->shutdown(new \PDOException("MySQL has gone away"));'
            . 'echo "CONTINUED";';
        $process = new Process([PHP_BINARY, '-r', $code]);
        $process->run();

        self::assertSame(1, $process->getExitCode(), $process->getErrorOutput());
        self::assertSame('', $process->getOutput());
    }
}
