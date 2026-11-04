<?php

declare(strict_types=1);

namespace Windwalker\Core\Database;

use PDOException;
use Throwable;
use Windwalker\Core\Manager\Logger;
use Windwalker\Database\Driver\AbstractDriver;
use Windwalker\Database\Exception\DatabaseException;
use Windwalker\DI\Attributes\Service;

#[Service]
class DatabaseConnectionRecovery
{
    public const string MESSAGE = 'Database connection lost. Shutting down worker (exit code 1); process manager must restart it.';

    public static function isConnectionFailure(Throwable $exception): bool
    {
        do {
            $message = strtolower($exception->getMessage());
            $isDatabaseError = $exception instanceof PDOException
                || $exception instanceof DatabaseException
                || $exception instanceof \mysqli_sql_exception
                || str_contains($message, 'sqlstate[')
                || str_contains($message, 'mysql server')
                || str_starts_with($message, 'mysql:');

            if ($isDatabaseError) {
                $sqlState = $exception instanceof PDOException
                    ? (string) ($exception->errorInfo[0] ?? $exception->getCode())
                    : '';

                if (preg_match('/sqlstate\[([a-z0-9]{5})\]/i', $message, $matches)) {
                    $sqlState = strtoupper($matches[1]);
                }

                if (
                    $sqlState === '08004'
                    || in_array(substr($sqlState, 0, 2), ['22', '23', '28', '40', '42'], true)
                ) {
                    $exception = $exception->getPrevious();

                    continue;
                }

                if (in_array($sqlState, ['08000', '08001', '08003', '08006', '08007', '08S01', '57P01', '57P02', '57P03'], true)) {
                    return true;
                }

                if (AbstractDriver::shouldAutoReconnect($exception)) {
                    return true;
                }

                $keywords = [
                    'connection refused',
                    'connection reset',
                    'connection aborted',
                    'connection closed',
                    'connection is closed',
                    'connection was closed',
                    'connection has been closed',
                    'connection terminated',
                    'closed connection',
                    'connection not open',
                    'server closed the connection unexpectedly',
                    'server shutdown in progress',
                    'terminating connection',
                    'no connection to the server',
                    'broken pipe',
                    'communication link failure',
                    'ssl connection has been closed unexpectedly',
                    'unexpected eof',
                    'ssl syscall error: eof detected',
                    'error while sending query packet',
                ];

                if (array_any($keywords, static fn(string $keyword) => str_contains($message, $keyword))) {
                    return true;
                }

                $driverCode = $exception instanceof PDOException
                    ? ($exception->errorInfo[1] ?? null)
                    : $exception->getCode();

                if (in_array((int) $driverCode, [1053, 2002, 2003, 2006, 2013, 2055], true)) {
                    return true;
                }
            }

            $exception = $exception->getPrevious();
        } while ($exception !== null);

        return false;
    }

    public function shutdown(Throwable $exception): never
    {
        Logger::error('system/database-recovery', $exception);
        Logger::warning('system/database-recovery', self::MESSAGE);
        exit(1);
    }
}
