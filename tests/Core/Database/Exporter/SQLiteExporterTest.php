<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Database\Exporter;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\DatabaseExportService;
use Windwalker\Core\Database\Exporter\ExporterFactory;
use Windwalker\Core\Database\Exporter\SQLiteExporter;
use Windwalker\Database\DatabaseFactory;

class SQLiteExporterTest extends TestCase
{
    #[Test]
    #[TestWith([false, false])]
    #[TestWith([false, true])]
    #[TestWith([true, false])]
    #[TestWith([true, true])]
    public function fileDatabaseRestoresThroughAvailableCliTools(bool $native, bool $compress): void
    {
        $dir = dirname(__DIR__, 4) . '/tmp/sqlite-cli-' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);
        $source = $dir . '/source.sqlite';
        $restorePath = $dir . '/restored.sqlite';
        $db = new DatabaseFactory()->create('pdo_sqlite', ['dsn' => 'sqlite:' . $source]);
        $binary = new ExecutableFinder()->find('sqlite3');
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('path')->with('@temp')->willReturn($dir);
        $app->expects($binary ? $this->atLeastOnce() : $this->never())
            ->method('createProcess')->willReturnCallback(static fn(array $command) => new Process($command));
        /**
         * Forces the native branch while still executing the installed SQLite CLI.
         * Remove when SQLite CLI/native round-trip coverage is no longer needed.
         */
        $exporter = new class ($db, $app) extends SQLiteExporter {
            public bool $native = false;
            public ?string $binary = null;

            protected function findExecutable(string $name, string $environment): ?string
            {
                return $name === 'sqlite3' ? $this->binary : parent::findExecutable($name, $environment);
            }

            protected function exportSqlCli(string $file): ?string
            {
                return $this->native ? null : parent::exportSqlCli($file);
            }
        };
        $exporter->native = $native;
        $exporter->binary = $binary;
        $factory = $this->createStub(ExporterFactory::class);
        $factory->method('createExporter')->willReturn($exporter);
        $resultPath = null;
        $restore = null;

        try {
            $db->execute('CREATE TABLE example (id INTEGER PRIMARY KEY, label TEXT UNIQUE, payload BLOB)');
            $db->execute("INSERT INTO example VALUES (1, 'quote''and newline' || char(10), X'0001FF')");
            $result = new DatabaseExportService($factory, $app, $db)->exportTo(
                $dir . '/backup.sql',
                new BufferedOutput(),
                ['compress' => $compress]
            );
            $resultPath = $result->getPathname();
            $content = file_get_contents($resultPath);
            if ($compress) {
                self::assertSame("\x1f\x8b", substr($content, 0, 2));
                $content = gzdecode($content);
            }
            if ($native && $binary !== null) {
                self::assertStringEndsWith('.sqlite' . ($compress ? '.gz' : ''), $resultPath);
                self::assertStringStartsWith("SQLite format 3\0", $content);
                file_put_contents($restorePath, $content);
                $restore = new PDO('sqlite:' . $restorePath);
            } else {
                self::assertStringEndsWith('.sql' . ($compress ? '.gz' : ''), $resultPath);
                $restore = new PDO('sqlite::memory:');
                $restore->exec($content);
            }
            self::assertSame(1, (int) $restore->query('SELECT count(*) FROM example')->fetchColumn());
            self::assertSame(
                "quote'and newline\n",
                $restore->query('SELECT label FROM example')->fetchColumn()
            );
            self::assertSame('0001FF', $restore->query('SELECT hex(payload) FROM example')->fetchColumn());
        } finally {
            $restore = null;
            $db->disconnect();
            foreach ([$source, $restorePath, $resultPath] as $file) {
                if ($file !== null && is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($dir);
        }
    }

    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function phpFallbackRestoresTablesRowsIndexesViewsTriggersAndSequences(bool $compress): void
    {
        $dir = dirname(__DIR__, 4) . '/tmp/sqlite-export-' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);
        $db = new DatabaseFactory()->create('pdo_sqlite', ['dsn' => 'sqlite::memory:']);
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('path')->with('@temp')->willReturn($dir);
        $app->expects($this->never())->method('createProcess');
        $app->expects($this->never())->method('runProcess');
        $path = $dir . '/backup.sql';
        $outputPath = $path . ($compress ? '.gz' : '');

        try {
            $db->execute('CREATE TABLE parent (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE)');
            $db->execute('CREATE TABLE child (id INTEGER PRIMARY KEY, parent_id INTEGER REFERENCES parent(id), data BLOB, '
                . 'note TEXT, calculated TEXT GENERATED ALWAYS AS (note || note) STORED)');
            $db->execute('CREATE INDEX child_note ON child(note)');
            $db->execute('CREATE VIEW parent_names AS SELECT name FROM parent');
            $db->execute('CREATE TRIGGER normalize_name AFTER INSERT ON parent BEGIN '
                . 'UPDATE parent SET name=upper(name) WHERE id=new.id; END');
            $db->execute("INSERT INTO parent(id, name) VALUES (9, 'example'), (99, 'deleted')");
            $db->execute('DELETE FROM parent WHERE id=99');
            $db->execute("INSERT INTO child VALUES (1, 9, X'0001FF', 'quote''and newline' || char(10))");
            $db->execute("INSERT INTO child VALUES (2, 9, NULL, 'before' || char(0) || 'after')");
            $output = new BufferedOutput();
            $result = new DatabaseExportService(new ExporterFactory(), $app, $db)->exportTo(
                $path,
                $output,
                ['compress' => $compress]
            );
            $sql = file_get_contents($result->getPathname());
            if ($compress) {
                self::assertSame("\x1f\x8b", substr($sql, 0, 2));
                $sql = gzdecode($sql);
            }
            $restore = new PDO('sqlite::memory:');
            $restore->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $restore->exec($sql);

            self::assertSame('EXAMPLE', $restore->query('SELECT name FROM parent_names')->fetchColumn());
            self::assertSame('0001FF', $restore->query('SELECT hex(data) FROM child')->fetchColumn());
            self::assertSame("before\0after", $restore->query('SELECT note FROM child WHERE id=2')->fetchColumn());
            self::assertSame(
                "quote'and newline\nquote'and newline\n",
                $restore->query('SELECT calculated FROM child')->fetchColumn()
            );
            $restore->exec("INSERT INTO parent(name) VALUES ('next')");
            self::assertSame(100, (int) $restore->lastInsertId());
            self::assertSame('NEXT', $restore->query('SELECT name FROM parent WHERE id=100')->fetchColumn());
            self::assertStringContainsString('Fallback to PHP SQL export', $output->fetch());
            self::assertSame(filesize($outputPath), $result->getSize());
        } finally {
            $db->disconnect();
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
            rmdir($dir);
        }
    }
}
