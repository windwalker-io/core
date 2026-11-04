<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Database\Exporter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\DatabaseExportService;
use Windwalker\Core\Database\Exporter\ExporterFactory;
use Windwalker\Core\Database\Exporter\OracleExporter;
use Windwalker\Core\Database\Exporter\PostgresqlExporter;
use Windwalker\Core\Database\Exporter\SQLiteExporter;
use Windwalker\Core\Database\Exporter\SqlsrvExporter;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Driver\DriverOptions;
use Windwalker\Database\Manager\SchemaManager;
use Windwalker\Filesystem\Path;
use Windwalker\Tests\Core\Database\Exporter\Stub\CliExporterHarness;

class CliExporterTest extends TestCase
{
    public static function exportModes(): iterable
    {
        foreach (['pgsql', 'sqlserver', 'sqlite', 'oracle'] as $platform) {
            foreach ([
                'sql', 'native', 'php', 'missing', 'empty', 'start-failure', 'php-gzip', 'gzip-failure', 'php-error',
            ] as $mode) {
                foreach ([false, true] as $compress) {
                    yield "{$platform}-{$mode}-" . (int) $compress => [$platform, $mode, $compress];
                }
            }
        }
        yield 'pgsql-dsn' => [
            'pgsql', 'sql', false,
            "pgsql:host='db.example';port='6543';dbname='backup=fixture';user='tester';password='pa:ss=word'",
        ];
        yield 'sqlserver-dsn' => [
            'sqlserver', 'sql', false,
            'sqlsrv:Server=tcp:db.example,1544;Database=backup=fixture;UID=tester;PWD=pa:ss=word',
        ];
        yield 'oracle-dsn' => [
            'oracle', 'sql', false, 'oci:dbname=//db.example:1522/FREE;charset=AL32UTF8',
        ];
        yield 'oracle-error-with-zero-exit' => ['oracle', 'oracle-warning', false];
    }

    #[Test]
    #[DataProvider('exportModes')]
    public function selectsSqlThenNativeThenPhpWithoutMixingPartialOutput(
        string $platform,
        string $mode,
        bool $compress,
        ?string $dsn = null
    ): void {
        $dir = dirname(__DIR__, 4) . '/tmp/cli-export-' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);
        $source = $dir . '/source.sqlite';
        file_put_contents($source, 'source fixture');
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('path')->willReturn($dir);
        $db = $this->createStub(DatabaseAdapter::class);
        $db->method('getOptions')->willReturn(new DriverOptions(
            host: 'localhost',
            file: $source,
            dbname: 'example',
            user: 'example',
            password: 'not-logged',
            dsn: $dsn
        ));
        $schema = $this->createStub(SchemaManager::class);
        $schema->method('getTables')->willReturn([]);
        $db->method('getSchemaManager')->willReturn($schema);
        $exporter = match ($platform) {
            'pgsql' => new class ($db, $app) extends PostgresqlExporter {
                use CliExporterHarness;
            },
            'sqlserver' => new class ($db, $app) extends SqlsrvExporter {
                use CliExporterHarness;
            },
            'sqlite' => new class ($db, $app) extends SQLiteExporter {
                use CliExporterHarness;
            },
            'oracle' => new class ($db, $app) extends OracleExporter {
                use CliExporterHarness;
            },
        };
        $calls = [];
        $processes = [];
        $exporter->cliAvailable = $mode !== 'missing';
        $exporter->gzipAvailable = $mode !== 'php-gzip';
        $exporter->phpFails = $mode === 'php-error';
        $app->method('createProcess')->willReturnCallback(
            static function (array $command) use (&$calls, &$processes, $mode): Process {
                $calls[] = $command;
                if ($mode === 'start-failure') {
                    return new Process(['windwalker-nonexistent-export-tool-' . bin2hex(random_bytes(6))]);
                }
                $tool = $command[0];
                if ($tool === 'gzip') {
                    return new Process([
                        PHP_BINARY, '-r', 'echo gzencode(file_get_contents($argv[1])); exit('
                        . ($mode === 'gzip-failure' ? '1' : '0') . ');', $command[2],
                    ]);
                }
                $native = in_array($tool, ['sqlpackage', 'exp'], true)
                    || in_array('--format=custom', $command, true)
                    || str_starts_with(end($command), '.backup');
                $success = $native ? in_array($mode, ['native', 'oracle-warning'], true)
                    : in_array($mode, ['sql', 'php-gzip', 'gzip-failure'], true);
                $payload = $success ? ($native ? 'NATIVE DATA' : 'CLI SQL') : 'PARTIAL OUTPUT';
                if ($mode === 'empty') {
                    $payload = '';
                    $success = true;
                }
                $file = null;
                foreach ($command as $i => $argument) {
                    if ($argument === '--file-path') {
                        $file = $command[$i + 1];
                    }
                    foreach (['--file=', '/TargetFile:', 'FILE='] as $prefix) {
                        if (str_starts_with($argument, $prefix)) {
                            $file = substr($argument, strlen($prefix));
                        }
                    }
                    if (str_starts_with($argument, ".backup '")) {
                        $file = str_replace("''", "'", substr($argument, 9, -1));
                    }
                }
                $code = $file
                    ? 'file_put_contents($argv[1], $argv[2]);'
                    : 'echo $argv[2];';
                $diagnostic = $mode === 'oracle-warning' && $native
                    ? 'EXP-00056: ORA-01466: table definition has changed'
                    : 'diagnostic only';
                $code .= 'fwrite(STDERR, ' . var_export($diagnostic, true) . '); exit(' . ($success ? '0' : '1') . ');';

                return $processes[] = new Process([PHP_BINARY, '-r', $code, $file ?? '', $payload]);
            }
        );
        $factory = $this->createStub(ExporterFactory::class);
        $factory->method('createExporter')->willReturn($exporter);
        $path = $dir . '/backup.sql';
        $extension = $mode === 'native'
            ? match ($platform) {
                'pgsql' => 'dump',
                'sqlserver' => 'bacpac',
                'sqlite' => 'sqlite',
                'oracle' => 'dmp',
            } : 'sql';
        $outputPath = $dir . '/backup.' . $extension . ($compress ? '.gz' : '');

        try {
            $output = new BufferedOutput();
            try {
                $result = new DatabaseExportService($factory, $app, $db)->exportTo(
                    $path,
                    $output,
                    ['compress' => $compress]
                );
            } catch (\DomainException $e) {
                if ($mode !== 'php-error') {
                    throw $e;
                }
                self::assertSame('Unsupported PHP fixture schema.', $e->getMessage());
                self::assertFileDoesNotExist($outputPath);
                self::assertSame(1, count(glob($dir . '/*')));

                return;
            }
            self::assertNotSame('php-error', $mode);
            self::assertSame(Path::clean($outputPath), Path::clean($result->getPathname()));
            $content = file_get_contents($outputPath);
            if ($compress) {
                self::assertSame("\x1f\x8b", substr($content, 0, 2));
                $content = gzdecode($content);
            }
            self::assertSame(match (true) {
                $mode === 'sql', $mode === 'php-gzip', $mode === 'gzip-failure' && !$compress => 'CLI SQL',
                $mode === 'native' => 'NATIVE DATA',
                default => 'PHP SQL',
            }, $content);
            self::assertSame(
                $compress && in_array($mode, ['sql', 'native', 'gzip-failure'], true),
                in_array('gzip', array_column($calls, 0), true)
            );
            self::assertStringNotContainsString('not-logged', $output->fetch());
            if ($dsn !== null) {
                if ($platform === 'pgsql') {
                    self::assertContains('--host=db.example', $calls[0]);
                    self::assertContains('--port=6543', $calls[0]);
                    self::assertContains('--dbname=backup=fixture', $calls[0]);
                    self::assertSame('pa:ss=word', $processes[0]->getEnv()['PGPASSWORD']);
                } elseif ($platform === 'sqlserver') {
                    self::assertContains('db.example,1544', $calls[0]);
                    self::assertContains('backup=fixture', $calls[0]);
                    self::assertSame('pa:ss=word', $processes[0]->getEnv()['MSSQL_SCRIPTER_PASSWORD']);
                } else {
                    self::assertStringContainsString(
                        '@//db.example:1522/FREE',
                        $processes[0]->getInput()
                    );
                }
            }
            self::assertSame(filesize($outputPath), $result->getSize());
            self::assertSame(2, count(glob($dir . '/*')));
            if ($mode === 'missing') {
                self::assertSame([], $calls);
            } else {
                self::assertSame(
                    match ($platform) {
                        'pgsql' => 'pg_dump',
                        'sqlserver' => 'mssql-scripter',
                        'sqlite' => 'sqlite3',
                        'oracle' => 'sql',
                    },
                    $calls[0][0]
                );
            }
        } finally {
            foreach ([$path, $outputPath, $source] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($dir);
        }
    }
}
