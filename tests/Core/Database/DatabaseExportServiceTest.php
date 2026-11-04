<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Database;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Process\Process;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\DatabaseExportService;
use Windwalker\Core\Database\Exporter\AbstractExporter;
use Windwalker\Core\Database\Exporter\ExporterFactory;
use Windwalker\Core\Database\Exporter\MySQLExporter;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Driver\AbstractDriver;
use Windwalker\Database\Driver\DriverOptions;
use Windwalker\Database\Manager\SchemaManager;

#[CoversClass(DatabaseExportService::class)]
#[CoversClass(AbstractExporter::class)]
#[CoversClass(MySQLExporter::class)]
class DatabaseExportServiceTest extends TestCase
{
    #[Test]
    #[TestWith([false])]
    #[TestWith([true])]
    public function phpCompressionIsReusableWithoutMysql(bool $compress): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->never())->method('runProcess');
        $app->expects($this->never())->method('createProcess');
        $db = $this->createStub(DatabaseAdapter::class);

        /**
         * Exercises the shared PHP compression helper without MySQL behavior.
         * Needed while database-independent compression is covered here.
         */
        $exporter = new class ($db, $app) extends AbstractExporter {
            protected function doExport(StreamInterface $stream, array $options = []): void
            {
                if ($options['compress'] ?? false) {
                    $stream = $this->createGzipStream($stream);
                }

                try {
                    $stream->write('database-independent SQL');
                } finally {
                    $stream->close();
                }
            }

            protected function getCreateTable(string $table): string
            {
                return '';
            }

            protected function getInserts(string $table): string
            {
                return '';
            }
        };
        $factory = $this->createStub(ExporterFactory::class);
        $factory->method('createExporter')->willReturn($exporter);
        $dir = (defined('WINDWALKER_TEMP') ? WINDWALKER_TEMP : dirname(__DIR__, 3) . '/tmp')
            . '/export-compression-' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);
        $path = $dir . '/backup.sql';
        $outputPath = $path . ($compress ? '.gz' : '');

        try {
            new DatabaseExportService($factory, $app, $db)->exportTo(
                $path,
                new NullOutput(),
                ['compress' => $compress]
            );
            $content = file_get_contents($outputPath);

            self::assertSame('database-independent SQL', $compress ? gzdecode($content) : $content);
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }

            rmdir($dir);
        }
    }

    #[Test]
    #[TestWith([true, true, false])]
    #[TestWith([true, true, true])]
    #[TestWith([true, false, false])]
    #[TestWith([true, false, true])]
    #[TestWith([false, false, false])]
    #[TestWith([true, true, false, true])]
    #[TestWith([true, true, false, false, false])]
    #[TestWith([true, false, false, false, false])]
    public function exportsValidContentWithCliAndPhpCompression(
        bool $compress,
        bool $gzipAvailable,
        bool $dumpFails,
        bool $gzipFails = false,
        bool $dumpAvailable = true
    ): void {
        $app = $this->createStub(ApplicationInterface::class);
        $db = $this->createStub(DatabaseAdapter::class);
        $driver = $this->createStub(AbstractDriver::class);
        $driver->method('getOptions')->willReturn(new DriverOptions(dbname: 'backup_test'));
        $db->method('getDriver')->willReturn($driver);
        $schema = $this->createStub(SchemaManager::class);
        $schema->method('getTables')->willReturn([(object) ['tableName' => 'example']]);
        $db->method('getSchemaManager')->willReturn($schema);

        $probe = new Process([PHP_BINARY, '-r', 'exit(' . ($gzipAvailable ? '0' : '1') . ');']);
        $probe->run();
        $probes = [];
        $app->method('runProcess')->willReturnCallback(
            static function (string|array $command) use (&$probes, $probe): Process {
                $probes[] = $command;

                return $probe;
            }
        );

        $commands = [];
        $app->method('createProcess')->willReturnCallback(
            static function (string|array $command) use (&$commands, $dumpFails, $gzipFails): Process {
                $commands[] = $command;

                if ($command === 'gzip') {
                    return new Process(
                        [
                            PHP_BINARY,
                            '-r',
                            'echo gzencode(stream_get_contents(STDIN)); exit(' . ($gzipFails ? '1' : '0') . ');',
                        ]
                    );
                }

                return new Process(
                    [
                        PHP_BINARY,
                        '-r',
                        'fwrite(STDERR, "diagnostic only"); echo "dump SQL"; exit(' . ($dumpFails ? '1' : '0') . ');',
                    ]
                );
            }
        );

        /**
         * Supplies deterministic SQL without a database or mysqldump binary.
         * Needed while the export compression and fallback paths are covered here.
         */
        $exporter = new class ($db, $app) extends MySQLExporter {
            public bool $dumpAvailable = true;

            protected function findMysqldump(): ?string
            {
                return $this->dumpAvailable ? 'mysqldump' : null;
            }

            protected function createPasswordCnfFile(DriverOptions $options): string
            {
                return 'unused.cnf';
            }

            protected function getCreateTable(string $table): string
            {
                return 'CREATE TABLE example (id INT)';
            }

            protected function getInserts(string $table): string
            {
                return 'INSERT INTO example VALUES (1);';
            }
        };
        $exporter->dumpAvailable = $dumpAvailable;
        $factory = $this->createStub(ExporterFactory::class);
        $factory->method('createExporter')->willReturn($exporter);

        $dir = (defined('WINDWALKER_TEMP') ? WINDWALKER_TEMP : dirname(__DIR__, 3) . '/tmp')
            . '/export-compression-' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);
        $path = $dir . '/backup.sql';
        $outputPath = $path . ($compress ? '.gz' : '');

        try {
            $result = new DatabaseExportService($factory, $app, $db)->exportTo(
                $path,
                new NullOutput(),
                options: ['compress' => $compress]
            );
            $content = file_get_contents($outputPath);

            self::assertSame($outputPath, $result->getPathname());
            self::assertSame(strlen($content), $result->getSize());

            if ($compress) {
                self::assertSame("\x1f\x8b", substr($content, 0, 2));
                $content = gzdecode($content);
            }

            self::assertSame(
                !$dumpAvailable || $dumpFails || $gzipFails
                    ? 'DROP TABLE `example` IF EXISTS;' . "\n"
                        . 'CREATE TABLE example (id INT)INSERT INTO example VALUES (1);'
                    : 'dump SQL',
                $content
            );
            self::assertSame($compress && $dumpAvailable, $probes !== []);
            self::assertSame(
                $compress && $dumpAvailable && $gzipAvailable,
                in_array('gzip', $commands, true)
            );
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }

            rmdir($dir);
        }
    }
}
