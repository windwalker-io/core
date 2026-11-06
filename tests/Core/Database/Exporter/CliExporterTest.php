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

/**
 * Tests CLI export selection and fallback without any database server.
 *
 * What: for PostgreSQL, SQL Server, SQLite and Oracle, checks that the exporter chooses SQL output,
 * then native output, then PHP output, and never mixes partial output from a failed tool into the result.
 * Covers missing tools, empty output, start failures, gzip success and failure, PHP errors and Oracle warnings.
 *
 * How: CliExporterHarness replaces CLI discovery and the PHP exporter with fixtures. The application
 * createProcess() returns fake commands whose output depends on the data-provider mode.
 */
class CliExporterTest extends TestCase
{
    public static function exportModes(): iterable
    {
        $platforms = ['pgsql', 'sqlserver', 'sqlite', 'oracle'];
        $modes = [
            'sql', 'native', 'php', 'missing', 'empty', 'start-failure', 'php-gzip', 'gzip-failure', 'php-error',
        ];

        foreach ($platforms as $platform) {
            foreach ($modes as $mode) {
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

    /**
     * Commands received by createProcess() (including gzip), used to verify the tools and arguments invoked.
     *
     * @var list<list<string>>
     */
    private array $commands = [];

    /**
     * Fake processes run as export tools (excluding gzip and start-failure), used to verify env vars and stdin.
     *
     * @var list<Process>
     */
    private array $processes = [];

    #[Test]
    #[DataProvider('exportModes')]
    public function selectsSqlThenNativeThenPhpWithoutMixingPartialOutput(
        string $platform,
        string $mode,
        bool $compress,
        ?string $dsn = null
    ): void {
        $this->commands = [];
        $this->processes = [];

        // 1. Create the work directory and source file (under core's tmp/)
        $dir = $this->createWorkDir();
        $source = $dir . '/source.sqlite';
        file_put_contents($source, 'source fixture');

        // 2. Create the fake application and database; createProcess() returns fake processes
        $app = $this->createApplication($dir, $mode);
        $db = $this->createDatabase($source, $dsn);

        // 3. Create the exporter under test and its factory
        $exporter = $this->createExporter($platform, $db, $app, $mode);
        $factory = $this->createStub(ExporterFactory::class);
        $factory->method('createExporter')->willReturn($exporter);

        // 4. Target path: native mode uses the platform's native extension, otherwise .sql
        $path = $dir . '/backup.sql';
        $extension = $this->expectedExtension($platform, $mode);
        $outputPath = $dir . '/backup.' . $extension . ($compress ? '.gz' : '');

        try {
            $output = new BufferedOutput();

            // 5. Run the export
            try {
                $result = new DatabaseExportService($factory, $app, $db)->exportTo(
                    $path,
                    $output,
                    ['compress' => $compress]
                );
            } catch (\DomainException $e) {
                // php-error mode is expected to throw, and must leave no output file behind
                if ($mode !== 'php-error') {
                    throw $e;
                }

                self::assertSame('Unsupported PHP fixture schema.', $e->getMessage());
                self::assertFileDoesNotExist($outputPath);
                self::assertSame(1, count(glob($dir . '/*'))); // Only the source file remains

                return;
            }

            self::assertNotSame('php-error', $mode);

            // 6. Verify the result path and content
            self::assertSame(Path::clean($outputPath), Path::clean($result->getPathname()));
            $content = file_get_contents($outputPath);
            if ($compress) {
                // 1f 8b is the gzip magic number
                self::assertSame("\x1f\x8b", substr($content, 0, 2));
                $content = gzdecode($content);
            }
            self::assertSame($this->expectedContent($mode, $compress), $content);

            // gzip is only invoked when compression is requested and SQL/native output is produced
            $expectGzip = $compress && in_array($mode, ['sql', 'native', 'gzip-failure'], true);
            self::assertSame($expectGzip, in_array('gzip', array_column($this->commands, 0), true));

            // 7. Verify the output does not leak the DB password, and the file size matches the result
            self::assertStringNotContainsString('not-logged', $output->fetch());
            self::assertSame(filesize($outputPath), $result->getSize());
            self::assertSame(2, count(glob($dir . '/*'))); // Source file + backup file

            // 8. Verify the DSN arguments and the first tool invoked
            if ($dsn !== null) {
                $this->assertDsnIsPassedToTool($platform);
            }
            if ($mode === 'missing') {
                self::assertSame([], $this->commands);
            } else {
                self::assertSame($this->firstTool($platform), $this->commands[0][0]);
            }
        } finally {
            $this->removeWorkDir([$path, $outputPath, $source], $dir);
        }
    }

    private function createWorkDir(): string
    {
        $dir = dirname(__DIR__, 4) . '/tmp/cli-export-' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);

        return $dir;
    }

    private function removeWorkDir(array $files, string $dir): void
    {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($dir);
    }

    private function createApplication(string $dir, string $mode): ApplicationInterface
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('path')->willReturn($dir);
        $app->method('createProcess')->willReturnCallback(
            fn (array $command): Process => $this->fakeProcess($command, $mode)
        );

        return $app;
    }

    private function createDatabase(string $source, ?string $dsn): DatabaseAdapter
    {
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

        return $db;
    }

    private function createExporter(
        string $platform,
        DatabaseAdapter $db,
        ApplicationInterface $app,
        string $mode
    ): PostgresqlExporter|SqlsrvExporter|SQLiteExporter|OracleExporter {
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

        // missing: CLI tool not found; php-gzip: gzip unavailable; php-error: PHP exporter fails
        $exporter->cliAvailable = $mode !== 'missing';
        $exporter->gzipAvailable = $mode !== 'php-gzip';
        $exporter->phpFails = $mode === 'php-error';

        return $exporter;
    }

    /**
     * Decide the fake process behavior per command: gzip, start failure, or a simulated export tool.
     */
    private function fakeProcess(array $command, string $mode): Process
    {
        $this->commands[] = $command;

        if ($mode === 'start-failure') {
            return new Process(['windwalker-nonexistent-export-tool-' . bin2hex(random_bytes(6))]);
        }

        if ($command[0] === 'gzip') {
            return $this->fakeGzip($command, $mode);
        }

        return $this->fakeDumpTool($command, $mode);
    }

    private function fakeGzip(array $command, string $mode): Process
    {
        $exitCode = $mode === 'gzip-failure' ? 1 : 0;
        $code = 'echo gzencode(file_get_contents($argv[1])); exit(' . $exitCode . ');';

        return new Process([PHP_BINARY, '-r', $code, $command[2]]);
    }

    /**
     * Simulate an export tool: the mode decides success and output, written to the target file or stdout.
     */
    private function fakeDumpTool(array $command, string $mode): Process
    {
        $native = $this->isNativeCommand($command);

        // Native tools succeed only in native / oracle-warning; SQL tools only in sql / php-gzip / gzip-failure
        $success = $native
            ? in_array($mode, ['native', 'oracle-warning'], true)
            : in_array($mode, ['sql', 'php-gzip', 'gzip-failure'], true);
        $payload = $success ? ($native ? 'NATIVE DATA' : 'CLI SQL') : 'PARTIAL OUTPUT';

        // empty: succeeds with no output
        if ($mode === 'empty') {
            $payload = '';
            $success = true;
        }

        $file = $this->outputFileOf($command);

        // Write to the target file when there is one, otherwise to stdout
        $code = $file
            ? 'file_put_contents($argv[1], $argv[2]);'
            : 'echo $argv[2];';

        // oracle-warning: native tool exits 0 but prints an ORA-01466 warning, which must count as a failure
        $diagnostic = $mode === 'oracle-warning' && $native
            ? 'EXP-00056: ORA-01466: table definition has changed'
            : 'diagnostic only';
        $code .= 'fwrite(STDERR, ' . var_export($diagnostic, true) . '); exit(' . ($success ? '0' : '1') . ');';

        $process = new Process([PHP_BINARY, '-r', $code, $file ?? '', $payload]);
        $this->processes[] = $process;

        return $process;
    }

    private function isNativeCommand(array $command): bool
    {
        return in_array($command[0], ['sqlpackage', 'exp'], true)
            || in_array('--format=custom', $command, true)
            || str_starts_with(end($command), '.backup');
    }

    /**
     * Extract the target file path from each tool's argument format.
     */
    private function outputFileOf(array $command): ?string
    {
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

            // sqlite3 .backup command; single quotes are escaped as ''
            if (str_starts_with($argument, ".backup '")) {
                $file = str_replace("''", "'", substr($argument, 9, -1));
            }
        }

        return $file;
    }

    /**
     * Expected output content.
     */
    private function expectedContent(string $mode, bool $compress): string
    {
        // Native tool succeeded: use native output
        if ($mode === 'native') {
            return 'NATIVE DATA';
        }

        // CLI SQL succeeded; if gzip fails without compression requested, the raw CLI SQL is still usable
        if (in_array($mode, ['sql', 'php-gzip'], true) || ($mode === 'gzip-failure' && !$compress)) {
            return 'CLI SQL';
        }

        // Otherwise (missing tool, empty or partial output, Oracle warning, gzip failure when compressing) fall back to PHP export
        return 'PHP SQL';
    }

    private function expectedExtension(string $platform, string $mode): string
    {
        if ($mode !== 'native') {
            return 'sql';
        }

        return match ($platform) {
            'pgsql' => 'dump',
            'sqlserver' => 'bacpac',
            'sqlite' => 'sqlite',
            'oracle' => 'dmp',
        };
    }

    private function firstTool(string $platform): string
    {
        return match ($platform) {
            'pgsql' => 'pg_dump',
            'sqlserver' => 'mssql-scripter',
            'sqlite' => 'sqlite3',
            'oracle' => 'sql',
        };
    }

    private function assertDsnIsPassedToTool(string $platform): void
    {
        if ($platform === 'pgsql') {
            self::assertContains('--host=db.example', $this->commands[0]);
            self::assertContains('--port=6543', $this->commands[0]);
            self::assertContains('--dbname=backup=fixture', $this->commands[0]);
            self::assertSame('pa:ss=word', $this->processes[0]->getEnv()['PGPASSWORD']);
        } elseif ($platform === 'sqlserver') {
            self::assertContains('db.example,1544', $this->commands[0]);
            self::assertContains('backup=fixture', $this->commands[0]);
            self::assertSame('pa:ss=word', $this->processes[0]->getEnv()['MSSQL_SCRIPTER_PASSWORD']);
        } else {
            // Oracle connection info is passed via stdin
            self::assertStringContainsString('@//db.example:1522/FREE', $this->processes[0]->getInput());
        }
    }
}
