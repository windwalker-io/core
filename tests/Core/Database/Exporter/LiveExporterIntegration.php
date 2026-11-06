<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Database\Exporter;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\DatabaseExportService;
use Windwalker\Core\Database\Exporter\ExporterFactory;
use Windwalker\Core\Database\Exporter\OracleExporter;
use Windwalker\Core\Database\Exporter\PostgresqlExporter;
use Windwalker\Core\Database\Exporter\SqlsrvExporter;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\DatabaseFactory;
use Windwalker\Database\Driver\DriverOptions;
use Windwalker\Database\Driver\Pdo\DsnHelper;

/**
 * Live round trips against the server of the active WINDWALKER_TEST_DB_DSN.
 *
 * What: exports a disposable database (or Oracle schema), restores it into a second one,
 * and checks the restored data, constraints and indexes. Only the platform of the active DSN runs:
 * - PostgreSQL: php, sql (psql restore) and native (pg_dump/pg_restore) modes, each compressed or not.
 * - SQL Server: php mode only.
 * - Oracle: legacy exp/imp round trip (needs exp and imp via ORACLE_EXP_BINARY/ORACLE_IMP_BINARY or PATH).
 * - Other platforms: skipped; they are covered by CliExporterTest and SQLiteExporterTest.
 *
 * How: connects with the DSN's own credentials. Native and SQL modes need pg_dump, pg_restore and psql
 * on PATH; missing tools skip those modes. This class never calls docker directly; the optional
 * bin/oracle-docker-* wrappers reach a container only when ORACLE_EXP_BINARY/ORACLE_IMP_BINARY point to them.
 * Start database servers beforehand (for example `docker compose up -d` in the background).
 *
 * Note: the file name does not end in Test, so run it explicitly:
 * `vendor/bin/phpunit -c phpunit.ci.xml tests/Core/Database/Exporter/LiveExporterIntegration.php`.
 */
class LiveExporterIntegration extends TestCase
{
    /**
     * @return  array{platform: string, options: array<string, string>}|null
     */
    protected static function activeTarget(): ?array
    {
        $dsn = env('WINDWALKER_TEST_DB_DSN');

        if (!$dsn) {
            return null;
        }

        $options = DsnHelper::extract($dsn, $type);
        $platform = match (strtolower((string) $type)) {
            'pgsql', 'pdo_pgsql' => 'pgsql',
            'sqlsrv', 'pdo_sqlsrv' => 'sqlserver',
            'oci', 'pdo_oci', 'oracle', 'pdo_oracle' => 'oracle',
            default => null,
        };

        return $platform === null ? null : ['platform' => $platform, 'options' => $options];
    }

    protected static function activePlatform(): ?string
    {
        return self::activeTarget()['platform'] ?? null;
    }

    protected static function port(string $platform, array $options): int
    {
        return (int) ($options['port'] ?? match ($platform) {
            'pgsql' => 5432,
            'sqlserver' => 1433,
            default => 1521,
        });
    }

    protected static function serverDsn(string $platform, array $options, string $dbname): string
    {
        $host = $options['host'] ?? '127.0.0.1';
        $port = self::port($platform, $options);

        return match ($platform) {
            'pgsql' => sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $dbname),
            'sqlserver' => sprintf('sqlsrv:Server=%s,%d;Database=%s;TrustServerCertificate=1', $host, $port, $dbname),
            'oracle' => sprintf('oci:dbname=//%s:%d/%s', $host, $port, $dbname),
        };
    }

    /**
     * Options for creating a DatabaseAdapter on a given database of the active server.
     */
    protected static function databaseOptions(string $platform, array $options, string $dbname): array
    {
        return [
            'host' => $options['host'] ?? '127.0.0.1',
            'port' => self::port($platform, $options),
            'dbname' => $dbname,
            'user' => $options['user'] ?? '',
            'password' => $options['password'] ?? '',
            'dsn' => self::serverDsn($platform, $options, $dbname),
        ];
    }

    /**
     * Admin connection on the server's maintenance database, used to create and drop disposable databases.
     */
    protected static function connectAdmin(string $platform, array $options): PDO
    {
        $dbname = match ($platform) {
            'pgsql' => 'postgres',
            'sqlserver' => 'master',
            default => $options['dbname'] ?? 'FREEPDB1',
        };

        return new PDO(
            self::serverDsn($platform, $options, $dbname),
            $options['user'] ?? '',
            $options['password'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    /**
     * Runs a host PostgreSQL client tool against the active server.
     */
    protected static function pgTool(
        string $tool,
        array $options,
        string $dbname,
        array $args,
        ?string $input = null
    ): Process {
        return new Process(
            [
                $tool,
                '-h', $options['host'] ?? '127.0.0.1',
                '-p', (string) self::port('pgsql', $options),
                '-U', $options['user'] ?? '',
                '-d', $dbname,
                ...$args,
            ],
            env: ['PGPASSWORD' => $options['password'] ?? ''],
            input: $input,
            timeout: 120,
        );
    }

    /**
     * Resolves a legacy Oracle tool from its env override first, then from PATH.
     */
    protected static function oracleTool(string $name, string $environment): ?string
    {
        return env($environment) ?: new ExecutableFinder()->find($name);
    }

    protected static function removeDirectory(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($dir);
    }

    public static function modes(): iterable
    {
        $finder = new ExecutableFinder();
        $platform = self::activePlatform();
        $pgTools = $finder->find('pg_dump') && $finder->find('pg_restore') && $finder->find('psql');
        $modes = match ($platform) {
            'pgsql' => ['php', ...($pgTools ? ['sql', 'native'] : [])],
            'sqlserver' => ['php'],
            default => [],
        };

        if ($modes === []) {
            yield 'inactive-platform' => ['', false];

            return;
        }

        foreach ($modes as $mode) {
            foreach ([false, true] as $compress) {
                yield "{$platform}-{$mode}-" . (int) $compress => [$mode, $compress];
            }
        }
    }

    #[Test]
    #[DataProvider('modes')]
    public function restoresDisposableServerDatabase(string $mode, bool $compress): void
    {
        $platform = self::activePlatform();

        if (!in_array($platform, ['pgsql', 'sqlserver'], true)) {
            self::markTestSkipped('Active WINDWALKER_TEST_DB_DSN is not PostgreSQL or SQL Server.');
        }

        $pgsql = $platform === 'pgsql';
        $options = self::activeTarget()['options'];
        $admin = self::connectAdmin($platform, $options);
        $prefix = 'ww_export_' . bin2hex(random_bytes(6));
        $source = $prefix . '_src';
        $target = $prefix . '_dst';
        $dir = dirname(__DIR__, 4) . '/tmp/' . $prefix;
        mkdir($dir, 0777, true);
        $db = null;
        $restore = null;
        $created = [];
        $artifact = null;

        try {
            foreach ([$source, $target] as $name) {
                $admin->exec('CREATE DATABASE ' . $name);
                $created[] = $name;
            }

            $db = new DatabaseFactory()->create(
                $pgsql ? 'pdo_pgsql' : 'pdo_sqlsrv',
                self::databaseOptions($platform, $options, $source)
            );
            $db->execute($pgsql
                ? 'CREATE TABLE z_parent (id bigint GENERATED ALWAYS AS IDENTITY (START WITH 10 INCREMENT BY 2) PRIMARY KEY, '
                    . 'label text NOT NULL UNIQUE, enabled boolean DEFAULT true, created_at timestamp DEFAULT CURRENT_TIMESTAMP)'
                : 'CREATE TABLE z_parent (id bigint IDENTITY(10,2) PRIMARY KEY, label nvarchar(200) NOT NULL UNIQUE, '
                    . 'enabled bit DEFAULT 1, created_at datetime2 DEFAULT CURRENT_TIMESTAMP)');
            $db->execute($pgsql
                ? 'CREATE TABLE a_child (id integer PRIMARY KEY, parent_id bigint REFERENCES z_parent(id), '
                    . 'payload bytea, note text, computed integer GENERATED ALWAYS AS (id * 2) STORED)'
                : 'CREATE TABLE a_child (id int PRIMARY KEY, parent_id bigint REFERENCES z_parent(id), '
                    . 'payload varbinary(max), note nvarchar(max), computed AS (id * 2) PERSISTED, version rowversion)');
            $db->execute('CREATE INDEX child_note ON a_child(parent_id)');
            $db->execute("INSERT INTO z_parent(label) VALUES ('first'), ('deleted')");
            $db->execute("DELETE FROM z_parent WHERE label='deleted'");
            $payload = $pgsql ? "decode('0001ff', 'hex')" : '0x0001FF';
            $db->execute("INSERT INTO a_child(id,parent_id,payload,note) VALUES (1,10,{$payload},'quote''and text')");

            $app = $this->stubApplication($dir);
            if ($pgsql) {
                /**
                 * Forces the requested pg_dump mode on the host without changing export generation.
                 */
                $exporter = new class ($db, $app) extends PostgresqlExporter {
                    public string $mode = 'php';

                    protected function findExecutable(string $name, string $environment): ?string
                    {
                        return $name === 'pg_dump' && $this->mode !== 'php' ? 'pg_dump' : null;
                    }

                    protected function exportSqlCli(string $file): ?string
                    {
                        return $this->mode === 'native' ? null : parent::exportSqlCli($file);
                    }
                };
                $exporter->mode = $mode;
            } else {
                /**
                 * Forces PHP to exercise SQL Server catalogs against the live server.
                 */
                $exporter = new class ($db, $app) extends SqlsrvExporter {
                    protected function findExecutable(string $name, string $environment): ?string
                    {
                        return null;
                    }
                };
            }

            $factory = $this->stubExporterFactory($exporter);
            $output = new BufferedOutput();
            $file = new DatabaseExportService($factory, $app, $db)->exportTo(
                $dir . '/backup.sql',
                $output,
                ['compress' => $compress]
            );
            $artifact = $file->getPathname();
            self::assertSame($mode === 'native' ? 'dump' : 'sql', $exporter->getFileExtension(), $output->fetch());
            $content = file_get_contents($artifact);
            if ($compress) {
                self::assertSame("\x1f\x8b", substr($content, 0, 2));
                $content = gzdecode($content);
            }

            $restore = new PDO(
                self::serverDsn($platform, $options, $target),
                $options['user'] ?? '',
                $options['password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            if ($mode === 'native') {
                $native = $dir . '/restore.dump';
                file_put_contents($native, $content);
                self::pgTool('pg_restore', $options, $target, ['--exit-on-error', $native])->mustRun();
            } elseif ($mode === 'sql') {
                self::pgTool(
                    'psql',
                    $options,
                    $target,
                    ['-X', '-v', 'ON_ERROR_STOP=1'],
                    $content
                )->mustRun();
            } elseif ($pgsql) {
                $restore->exec($content);
            } else {
                foreach (preg_split('/;\s*(?:\r?\n|$)/', $content) as $statement) {
                    if (trim($statement) !== '') {
                        try {
                            $restore->exec($statement);
                        } catch (\PDOException $e) {
                            throw new \RuntimeException('Fixture restore failed for: ' . $statement, 0, $e);
                        }
                    }
                }
            }

            self::assertSame(1, (int) $restore->query('SELECT count(*) FROM a_child')->fetchColumn());
            self::assertSame("quote'and text", $restore->query('SELECT note FROM a_child')->fetchColumn());
            self::assertSame(2, (int) $restore->query('SELECT computed FROM a_child')->fetchColumn());
            self::assertSame(
                '0001ff',
                strtolower($restore->query($pgsql
                    ? "SELECT encode(payload, 'hex') FROM a_child"
                    : 'SELECT CONVERT(varchar(max),payload,2) FROM a_child')->fetchColumn())
            );
            $restore->exec("INSERT INTO z_parent(label) VALUES ('next')");
            self::assertSame(14, (int) $restore->query("SELECT id FROM z_parent WHERE label='next'")->fetchColumn());
            $defaults = $restore->query("SELECT enabled,created_at FROM z_parent WHERE label='next'")->fetch(PDO::FETCH_ASSOC);
            self::assertTrue((bool) $defaults['enabled']);
            self::assertNotNull($defaults['created_at']);
            self::assertGreaterThan(0, (int) $restore->query(
                $pgsql
                ? "SELECT count(*) FROM pg_indexes WHERE tablename='a_child' AND indexname='child_note'"
                : "SELECT count(*) FROM sys.indexes WHERE object_id=OBJECT_ID('a_child') AND name='child_note'"
            )->fetchColumn());
            self::assertSame(filesize($artifact), $file->getSize());
            $this->expectException(\PDOException::class);
            $restore->exec('INSERT INTO a_child(id,parent_id) VALUES (2,999)');
        } finally {
            $restore = null;
            $db?->disconnect();
            foreach (array_reverse($created) as $name) {
                if (!$pgsql) {
                    $admin->exec('ALTER DATABASE ' . $name . ' SET SINGLE_USER WITH ROLLBACK IMMEDIATE');
                }
                $admin->exec('DROP DATABASE ' . $name);
            }
            self::removeDirectory($dir);
        }
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function oracleLegacyNativeRestoresDisposableSchemas(bool $compress): void
    {
        $active = self::activeTarget();

        if (($active['platform'] ?? null) !== 'oracle') {
            self::markTestSkipped('Active WINDWALKER_TEST_DB_DSN is not Oracle.');
        }

        $exp = self::oracleTool('exp', 'ORACLE_EXP_BINARY');
        $imp = self::oracleTool('imp', 'ORACLE_IMP_BINARY');
        if (!$exp || !$imp) {
            self::markTestSkipped('Legacy Oracle exp and imp must be available (ORACLE_EXP_BINARY/ORACLE_IMP_BINARY or PATH).');
        }

        $options = $active['options'];
        $host = $options['host'] ?? '127.0.0.1';
        $port = self::port('oracle', $options);
        $service = $options['dbname'] ?? 'FREEPDB1';
        $admin = self::connectAdmin('oracle', $options);
        $prefix = 'WW_EXP_' . strtoupper(bin2hex(random_bytes(5)));
        $source = $prefix . '_SRC';
        $targetSchema = $prefix . '_DST';
        $password = 'Ww' . bin2hex(random_bytes(12));
        $dir = dirname(__DIR__, 4) . '/tmp/' . $prefix;
        mkdir($dir, 0777, true);
        $created = [];
        $artifact = null;
        $native = $dir . '/restore.dmp';

        try {
            foreach ([$source, $targetSchema] as $user) {
                $admin->exec("CREATE USER {$user} IDENTIFIED BY {$password} "
                    . 'DEFAULT TABLESPACE USERS QUOTA UNLIMITED ON USERS');
                $created[] = $user;
                $admin->exec("GRANT CREATE SESSION, CREATE TABLE TO {$user}");
            }

            $statements = [
                "CREATE TABLE {$source}.parent (id NUMBER PRIMARY KEY, label VARCHAR2(200))",
                "CREATE TABLE {$source}.child (id NUMBER PRIMARY KEY, parent_id NUMBER REFERENCES {$source}.parent(id), "
                . 'payload RAW(20))',
                "INSERT INTO {$source}.parent VALUES (1,'fixture')",
                "INSERT INTO {$source}.child VALUES (2,1,HEXTORAW('0001FF'))",
                'COMMIT',
            ];

            foreach ($statements as $sql) {
                $admin->exec($sql);
            }

            self::assertSame(1, (int) $admin->query(
                "SELECT COUNT(*) FROM {$source}.child WHERE RAWTOHEX(payload)='0001FF'"
            )->fetchColumn());
            // Legacy CONSISTENT snapshots can predate DDL within the same timestamp granule.
            sleep(2);

            $db = $this->createStub(DatabaseAdapter::class);
            $db->method('getOptions')->willReturn(new DriverOptions(
                host: $host,
                port: $port,
                dbname: $service,
                user: $source,
                password: $password
            ));
            $app = $this->stubApplication($dir);
            /**
             * Skips the PHP OCI export path so the legacy exp binary (ORACLE_EXP_BINARY) is used.
             */
            $exporter = new class ($db, $app) extends OracleExporter {
                protected function exportPhp(\Psr\Http\Message\StreamInterface $stream): void
                {
                    throw new \RuntimeException('Oracle native export failed; PHP OCI export is not expected.');
                }
            };
            $output = new BufferedOutput();
            $file = new DatabaseExportService($this->stubExporterFactory($exporter), $app, $db)->exportTo(
                $dir . '/backup.sql',
                $output,
                ['compress' => $compress]
            );
            $artifact = $file->getPathname();
            self::assertStringEndsWith('.dmp' . ($compress ? '.gz' : ''), $artifact, $output->fetch());
            $content = file_get_contents($artifact);
            if ($compress) {
                self::assertSame("\x1f\x8b", substr($content, 0, 2));
                $content = gzdecode($content);
            }
            self::assertNotEmpty($content);
            file_put_contents($native, $content);

            $import = new Process(
                [
                    $imp, ($options['user'] ?? '') . '@//' . $host . ':' . $port . '/' . $service,
                    'FILE=' . $native, 'FROMUSER=' . $source, 'TOUSER=' . $targetSchema,
                    'LOG=' . $dir . '/imp.log',
                ],
                input: ($options['password'] ?? '') . "\n",
                timeout: 120,
            );
            $import->mustRun();

            self::assertSame(1, (int) $admin->query(
                "SELECT COUNT(*) FROM {$targetSchema}.child WHERE id=2 AND parent_id=1 AND RAWTOHEX(payload)='0001FF'"
            )->fetchColumn(), $import->getOutput() . $import->getErrorOutput());
            self::assertSame(1, (int) $admin->query(
                "SELECT COUNT(*) FROM ALL_CONSTRAINTS WHERE OWNER='{$targetSchema}' AND TABLE_NAME='CHILD' "
                . "AND CONSTRAINT_TYPE='R' AND R_OWNER='{$targetSchema}' AND STATUS='ENABLED'"
            )->fetchColumn());
            self::assertSame(filesize($artifact), $file->getSize());
        } finally {
            foreach (array_reverse($created) as $user) {
                $admin->exec("DROP USER {$user} CASCADE");
            }
            self::removeDirectory($dir);
        }
    }

    protected function stubApplication(string $dir): ApplicationInterface
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('path')->willReturn($dir);
        $app->method('createProcess')->willReturnCallback(
            static fn(array $command): Process => new Process($command)
        );

        return $app;
    }

    protected function stubExporterFactory(object $exporter): ExporterFactory
    {
        $factory = $this->createStub(ExporterFactory::class);
        $factory->method('createExporter')->willReturn($exporter);

        return $factory;
    }
}
