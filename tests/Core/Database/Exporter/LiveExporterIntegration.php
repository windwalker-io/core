<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Database\Exporter;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\DatabaseExportService;
use Windwalker\Core\Database\Exporter\ExporterFactory;
use Windwalker\Core\Database\Exporter\OracleExporter;
use Windwalker\Core\Database\Exporter\PostgresqlExporter;
use Windwalker\Core\Database\Exporter\SqlsrvExporter;
use Windwalker\Database\DatabaseFactory;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Driver\DriverOptions;
use Windwalker\Utilities\Env;

/**
 * Opt-in round trips against explicitly configured disposable server databases.
 * Run explicitly with EXPORT_TEST_PG_PASSWORD, EXPORT_TEST_SQL_PASSWORD and
 * EXPORT_TEST_ORACLE_CONTAINER. All schemas/databases use unique disposable names.
 * Remove when equivalent server round trips replace these Docker integration cases.
 */
class LiveExporterIntegration extends TestCase
{
    protected function oracleSql(string $container, string $sql): string
    {
        return new Process(
            ['docker', 'exec', '-i', $container, 'sqlplus', '-s', '/ as sysdba'],
            input: "WHENEVER SQLERROR EXIT FAILURE\nWHENEVER OSERROR EXIT FAILURE\n"
                . "SET HEADING OFF\nSET FEEDBACK OFF\nSET PAGESIZE 0\nALTER SESSION SET CONTAINER=FREEPDB1;\n"
                . $sql . "\nEXIT SUCCESS\n"
        )->mustRun()->getOutput();
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function oracleLegacyNativeRestoresDisposableSchemas(bool $compress): void
    {
        $container = Env::get('EXPORT_TEST_ORACLE_CONTAINER');
        if (!$container) {
            throw new \RuntimeException('Explicit EXPORT_TEST_ORACLE_CONTAINER is required for Oracle tests.');
        }
        $prefix = 'WW_EXP_' . strtoupper(bin2hex(random_bytes(5)));
        $source = $prefix . '_SRC';
        $target = $prefix . '_DST';
        $password = 'Ww' . bin2hex(random_bytes(12));
        $dir = dirname(__DIR__, 4) . '/tmp/' . $prefix;
        mkdir($dir, 0777, true);
        $created = [];
        $artifact = null;
        $native = $dir . '/restore.dmp';
        $remote = '/tmp/' . $prefix . '.dmp';
        try {
            foreach ([$source, $target] as $user) {
                $this->oracleSql($container, "CREATE USER {$user} IDENTIFIED BY {$password} "
                    . 'DEFAULT TABLESPACE USERS QUOTA UNLIMITED ON USERS;');
                $created[] = $user;
                $this->oracleSql($container, "GRANT CREATE SESSION, CREATE TABLE TO {$user};");
            }
            $this->oracleSql($container, "CREATE TABLE {$source}.parent (id NUMBER PRIMARY KEY, label VARCHAR2(200));\n"
                . "CREATE TABLE {$source}.child (id NUMBER PRIMARY KEY, parent_id NUMBER REFERENCES {$source}.parent(id), "
                . "payload RAW(20));\nINSERT INTO {$source}.parent VALUES (1,'fixture');\n"
                . "INSERT INTO {$source}.child VALUES (2,1,HEXTORAW('0001FF'));\nCOMMIT;");
            self::assertSame('1', trim($this->oracleSql(
                $container,
                "SELECT COUNT(*) FROM {$source}.child WHERE RAWTOHEX(payload)='0001FF';"
            )));
            // Legacy CONSISTENT snapshots can predate DDL within the same timestamp granule.
            sleep(2);
            $db = $this->createStub(DatabaseAdapter::class);
            $db->method('getOptions')->willReturn(new DriverOptions(
                host: 'localhost',
                dbname: 'FREEPDB1',
                user: $source,
                password: $password
            ));
            $app = $this->createStub(ApplicationInterface::class);
            $app->method('path')->willReturn($dir);
            $exportProcesses = [];
            $app->method('createProcess')->willReturnCallback(static function (array $command) use (
                $container,
                &$exportProcesses
            ): Process {
                return $exportProcesses[] = new Process([
                    PHP_BINARY, __DIR__ . '/Stub/docker-oracle-export.php',
                    $container, json_encode($command, JSON_THROW_ON_ERROR),
                ]);
            });
            /**
             * Selects actual legacy exp inside Docker without a host OCI dependency.
             * Remove together with this explicit live integration test.
             */
            $exporter = new class ($db, $app) extends OracleExporter {
                protected function findExecutable(string $name, string $environment): ?string
                {
                    return $name === 'exp' ? 'exp' : null;
                }

                protected function exportPhp(\Psr\Http\Message\StreamInterface $stream): void
                {
                    throw new \RuntimeException('Oracle native export failed; host PHP OCI is unavailable.');
                }
            };
            $factory = $this->createStub(ExporterFactory::class);
            $factory->method('createExporter')->willReturn($exporter);
            $output = new BufferedOutput();
            $file = new DatabaseExportService($factory, $app, $db)->exportTo(
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
            new Process(['docker', 'cp', $native, $container . ':' . $remote])->mustRun();
            $import = new Process([
                'docker', 'exec', '-i', '-w', '/tmp', $container, 'imp', 'system@//localhost:1521/FREEPDB1',
                'FILE=' . $remote, 'FROMUSER=' . $source, 'TOUSER=' . $target, 'LOG=' . $remote . '.log',
            ]);
            $inspect = json_decode(new Process(['docker', 'inspect', $container])->mustRun()->getOutput(), true);
            foreach ($inspect[0]['Config']['Env'] as $entry) {
                if (str_starts_with($entry, 'ORACLE_PASSWORD=')) {
                    $import->setInput(substr($entry, strlen('ORACLE_PASSWORD=')) . "\n");
                }
            }
            $import->setTimeout(120)->mustRun();
            $result = $this->oracleSql(
                $container,
                "SELECT COUNT(*) FROM {$target}.child WHERE id=2 AND parent_id=1 AND RAWTOHEX(payload)='0001FF';"
            );
            self::assertSame('1', trim($result), $import->getOutput() . $import->getErrorOutput()
                . $this->oracleSql($container, "SELECT id,parent_id,RAWTOHEX(payload) FROM {$target}.child;")
                . $exportProcesses[0]->getOutput() . $exportProcesses[0]->getErrorOutput());
            self::assertSame('1', trim($this->oracleSql(
                $container,
                "SELECT COUNT(*) FROM DBA_CONSTRAINTS WHERE OWNER='{$target}' AND TABLE_NAME='CHILD' "
                    . "AND CONSTRAINT_TYPE='R' AND R_OWNER='{$target}' AND STATUS='ENABLED';"
            )));
            self::assertSame(filesize($artifact), $file->getSize());
        } finally {
            new Process([
                'docker', 'exec', '--user', 'root', $container, 'rm', '-f',
                $remote, $remote . '.log', $remote . '.log_sys.sql', substr($remote, 0, -4) . '_sys.sql',
            ])->mustRun();
            foreach (array_reverse($created) as $user) {
                $this->oracleSql($container, "DROP USER {$user} CASCADE;");
            }
            foreach ([$artifact, $native] as $file) {
                if ($file !== null && is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($dir);
        }
    }

    public static function modes(): iterable
    {
        foreach (['pgsql' => ['php', 'sql', 'native'], 'sqlserver' => ['php']] as $platform => $modes) {
            foreach ($modes as $mode) {
                foreach ([false, true] as $compress) {
                    yield "{$platform}-{$mode}-" . (int) $compress => [$platform, $mode, $compress];
                }
            }
        }
    }

    #[Test]
    #[DataProvider('modes')]
    public function restoresDisposableServerDatabase(string $platform, string $mode, bool $compress): void
    {
        $pgsql = $platform === 'pgsql';
        $password = Env::get($pgsql ? 'EXPORT_TEST_PG_PASSWORD' : 'EXPORT_TEST_SQL_PASSWORD');
        if (!$password) {
            throw new \RuntimeException('Explicit integration-test credentials are required.');
        }
        $user = $pgsql ? 'postgres' : 'sa';
        $adminDsn = $pgsql
            ? 'pgsql:host=127.0.0.1;port=5432;dbname=postgres'
            : 'sqlsrv:Server=127.0.0.1,1433;Database=master;TrustServerCertificate=1';
        $admin = new PDO($adminDsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
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
            $options = [
                'host' => '127.0.0.1', 'port' => $pgsql ? 5432 : 1433,
                'dbname' => $source, 'user' => $user, 'password' => $password,
            ];
            if (!$pgsql) {
                $options['dsn'] = str_replace('Database=master', 'Database=' . $source, $adminDsn);
            }
            $db = new DatabaseFactory()->create($pgsql ? 'pdo_pgsql' : 'pdo_sqlsrv', $options);
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
            $app = $this->createStub(ApplicationInterface::class);
            $app->method('path')->willReturn($dir);
            $app->method('createProcess')->willReturnCallback(static function (array $command): Process {
                return new Process([
                    PHP_BINARY, __DIR__ . '/Stub/docker-pg-dump.php',
                    Env::get('EXPORT_TEST_PG_CONTAINER') ?: 'windwalker_postgresql',
                    json_encode($command, JSON_THROW_ON_ERROR),
                ]);
            });
            if ($pgsql) {
                /**
                 * Selects actual Docker pg_dump or forces PHP without changing export generation.
                 * Remove together with this explicit live integration test.
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
                 * Remove together with this explicit live integration test.
                 */
                $exporter = new class ($db, $app) extends SqlsrvExporter {
                    protected function findExecutable(string $name, string $environment): ?string
                    {
                        return null;
                    }
                };
            }
            $factory = $this->createStub(ExporterFactory::class);
            $factory->method('createExporter')->willReturn($exporter);
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
                str_replace($pgsql ? 'dbname=postgres' : 'Database=master', ($pgsql ? 'dbname=' : 'Database=') . $target, $adminDsn),
                $user,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            if ($mode === 'native') {
                $native = $dir . '/restore.dump';
                file_put_contents($native, $content);
                $container = Env::get('EXPORT_TEST_PG_CONTAINER') ?: 'windwalker_postgresql';
                $remote = '/tmp/' . $prefix . '.dump';
                try {
                    new Process(['docker', 'cp', $native, $container . ':' . $remote])->mustRun();
                    new Process([
                        'docker', 'exec', $container, 'pg_restore', '-U', $user, '-d', $target, '--exit-on-error', $remote,
                    ])->mustRun();
                } finally {
                    new Process(['docker', 'exec', $container, 'rm', '-f', $remote])->mustRun();
                    unlink($native);
                }
            } elseif ($pgsql && $mode === 'sql') {
                new Process([
                    'docker', 'exec', '-i',
                    Env::get('EXPORT_TEST_PG_CONTAINER') ?: 'windwalker_postgresql',
                    'psql', '-X', '-v', 'ON_ERROR_STOP=1', '-U', $user, '-d', $target,
                ], input: $content)->mustRun();
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
            if ($artifact !== null && is_file($artifact)) {
                unlink($artifact);
            }
            rmdir($dir);
        }
    }
}
