<?php

declare(strict_types=1);

namespace Windwalker\Core\Database\Exporter;

use DomainException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Windwalker\Database\Driver\DriverOptions;
use Windwalker\Database\Manager\TableManager;
use Windwalker\Database\Schema\Ddl\Constraint;
use Windwalker\Database\Schema\Schema;
use Windwalker\Stream\Stream;

use const Windwalker\Stream\READ_ONLY_FROM_BEGIN;

abstract class AbstractCliExporter extends AbstractExporter
{
    protected string $fileExtension = 'sql';

    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    protected function options(): DriverOptions
    {
        $options = clone $this->db->getOptions();
        if (!$options->dsn) {
            return $options;
        }

        $parameters = explode(':', $options->dsn, 2)[1] ?? $options->dsn;
        foreach (explode(';', $parameters) as $parameter) {
            if (trim($parameter) === '') {
                continue;
            }
            if (!str_contains($parameter, '=')) {
                throw new DomainException('The export DSN must contain named connection parameters.');
            }
            [$key, $value] = explode('=', $parameter, 2);
            $key = strtolower(trim($key));
            if (strlen($value) >= 2 && $value[0] === "'" && str_ends_with($value, "'")) {
                $value = str_replace(['\\\\', "\\'"], ['\\', "'"], substr($value, 1, -1));
            }
            $key = match ($key) {
                'server' => 'host',
                'database' => 'dbname',
                'username', 'uid' => 'user',
                'pwd' => 'password',
                default => $key,
            };
            if (in_array($key, ['host', 'port', 'dbname', 'user', 'password', 'charset'], true)) {
                $options->$key = $value;
            }
        }

        return $options;
    }

    protected function findExecutable(string $name, string $environment): ?string
    {
        $configured = env($environment);

        if ($configured) {
            return (string) $configured;
        }

        return new ExecutableFinder()->find($name);
    }

    protected function process(array $command, array $environment = [], ?string $input = null): Process
    {
        $process = $this->app->createProcess($command);
        $process->setEnv($environment);
        $process->setInput($input);
        $process->setTimeout(null);

        return $process;
    }

    protected function writeProcess(Process $process, StreamInterface $stream): void
    {
        $process->mustRun(
            static function (string $type, string $buffer) use ($stream): void {
                if ($type === Process::OUT) {
                    $stream->write($buffer);
                }
            }
        );
    }

    /**
     * SQL and native CLI attempts write to an isolated local artifact before publishing it.
     * This prevents a failed process from contaminating the PHP fallback.
     */
    protected function doExport(StreamInterface $stream, array $options = []): void
    {
        $this->fileExtension = 'sql';
        $compress = (bool) ($options['compress'] ?? false);
        $uri = $stream->getMetadata('uri');
        $dir = $this->app->path('@temp');
        $scratch = tempnam($dir, 'db-export-');

        if ($scratch === false) {
            throw new RuntimeException('Unable to allocate the export scratch file.');
        }

        try {
            foreach (['sql', 'native'] as $format) {
                try {
                    $extension = $format === 'sql'
                        ? $this->exportSqlCli($scratch)
                        : $this->exportNativeCli($scratch);

                    if ($extension === null) {
                        $this->emitMessage("No usable {$format} export CLI; trying the next export method.");
                        continue;
                    }

                    clearstatcache(true, $scratch);

                    if (!is_file($scratch) || filesize($scratch) === 0) {
                        throw new DomainException("The {$format} export CLI produced no local artifact.");
                    }

                    $this->publishArtifact($scratch, $stream, $compress);
                    $this->fileExtension = $extension;

                    return;
                } catch (ProcessException|DomainException $e) {
                    // Process exception strings can contain credentials supplied by external tools.
                    $reason = $e instanceof ProcessFailedException
                        ? 'exit status ' . $e->getProcess()->getExitCode()
                        : 'unusable tool or artifact';
                    $this->emitMessage(
                        "{$format} export CLI failed ({$reason}); trying the next export method."
                    );
                    if (file_put_contents($scratch, '') === false) {
                        throw new RuntimeException('Unable to reset the export scratch file.', 0, $e);
                    }
                }
            }

            $this->emitMessage(
                'Fallback to PHP SQL export (limited logical backup); '
                . 'use database-native tools for complete backups including routines, grants and database settings.'
            );

            if ($compress) {
                if (!is_string($uri)) {
                    throw new RuntimeException('Unable to open the gzip backup destination.');
                }

                $stream = $this->createGzipStream($stream, $uri);
            }

            $this->exportPhp($stream);
        } finally {
            if ($compress) {
                $stream->close();
            }
            if (is_file($scratch) && !unlink($scratch)) {
                throw new RuntimeException('Unable to remove the export scratch file.');
            }
        }
    }

    protected function publishArtifact(string $file, StreamInterface $stream, bool $compress): void
    {
        if ($compress && ($gzip = $this->findExecutable('gzip', 'GZIP_BINARY'))) {
            $compressed = $file . '.gz';
            $output = Stream::fromFilePath($compressed, \Windwalker\Stream\WRITE_ONLY_RESET);
            try {
                $this->writeProcess($this->process([$gzip, '-c', $file]), $output);
                $output->close();
                $this->copyArtifact($compressed, $stream);
            } finally {
                $output->close();
                if (is_file($compressed) && !unlink($compressed)) {
                    throw new RuntimeException('Unable to remove the compressed export scratch file.');
                }
            }

            return;
        }

        $destination = $compress ? $this->createGzipStream($stream) : $stream;
        try {
            $this->copyArtifact($file, $destination);
        } finally {
            if ($compress) {
                $destination->close();
            }
        }
    }

    protected function copyArtifact(string $file, StreamInterface $stream): void
    {
        $source = Stream::fromFilePath($file, READ_ONLY_FROM_BEGIN);
        try {
            while (!$source->eof()) {
                $stream->write($source->read(1024 * 1024));
            }
        } finally {
            $source->close();
        }
    }

    abstract protected function exportSqlCli(string $file): ?string;

    protected function exportNativeCli(string $file): ?string
    {
        return null;
    }

    protected function exportPhp(StreamInterface $stream): void
    {
        $tables = $this->db->getSchemaManager()->getTables();
        $deferred = [];
        $foreignKeys = [];
        $this->writeSql($stream, $this->phpPreamble());
        $schemas = [];

        foreach ($tables as $table) {
            $name = $table->tableName;
            $manager = $this->db->getTableManager($name, true);
            $manager->schemaName = $table->tableSchema;
            if ($table->tableSchema && !isset($schemas[$table->tableSchema])) {
                $this->writeSchema($stream, $table->tableSchema);
                $schemas[$table->tableSchema] = true;
            }
            $statements = $this->tableDdl($manager);
            foreach ($statements as $sql) {
                $this->writeSql($stream, $sql);
            }

            $this->writeTableRows($stream, $name, $table->tableSchema);
            [$local, $references] = $this->tablePostDdl($manager);
            array_push($deferred, ...$local);
            array_push($foreignKeys, ...$references);
        }

        foreach ($deferred as $sql) {
            $this->writeSql($stream, $sql);
        }
        foreach ($foreignKeys as $sql) {
            $this->writeSql($stream, $sql);
        }

        $this->writeSql($stream, $this->phpEpilogue());
    }

    protected function tablePostDdl(TableManager $manager): array
    {
        $indexes = $manager->getIndexes();
        $constraints = $manager->getConstraints();
        $name = $manager->getName();
        $schema = $manager->schemaName;
        $local = $this->db->pretend(function () use ($indexes, $constraints, $name, $schema): void {
            $constraintNames = array_map(static fn($constraint) => $constraint->constraintName, $constraints);
            foreach ($indexes as $index) {
                if (!$index->isPrimary && !in_array($index->indexName, $constraintNames, true)) {
                    $this->db->getPlatform()->addIndex($name, $index, $schema);
                }
            }
            foreach ($constraints as $constraint) {
                if ($constraint->constraintType !== Constraint::TYPE_FOREIGN_KEY) {
                    $this->db->getPlatform()->addConstraint($name, $constraint, $schema);
                }
            }
        });
        $references = $this->db->pretend(function () use ($constraints, $name, $schema): void {
            foreach ($constraints as $constraint) {
                if ($constraint->constraintType === Constraint::TYPE_FOREIGN_KEY) {
                    $this->db->getPlatform()->addConstraint($name, $constraint, $schema);
                }
            }
        });

        return [$local, $references];
    }

    protected function tableDdl(TableManager $manager): array
    {
        $schema = new Schema($manager);
        foreach ($manager->getColumns() as $column) {
            $schema->addColumn($column);
        }

        return $this->db->pretend(fn() => $this->db->getPlatform()->createTable($schema));
    }

    protected function writeSchema(StreamInterface $stream, string $schema): void {}

    protected function writableColumns(TableManager $manager): array
    {
        return array_filter($manager->getColumns(), static fn($column) => !$column->isGenerated());
    }

    protected function phpPreamble(): string
    {
        return '';
    }

    protected function phpEpilogue(): string
    {
        return '';
    }

    protected function writeSql(StreamInterface $stream, string $sql): void
    {
        if (trim($sql) !== '') {
            $stream->write(rtrim($sql, ";\r\n ") . ";\n");
        }
    }

    protected function writeTableRows(StreamInterface $stream, string $table, ?string $schema = null): void
    {
        $name = $schema ? $schema . '.' . $table : $table;
        $columns = $this->db->getTableManager($table, true);
        $columns->schemaName = $schema;
        $writable = [];

        foreach ($this->writableColumns($columns) as $column) {
            $writable[$column->getColumnName()] = $column->getDataType();
        }

        if ($writable === []) {
            throw new DomainException("Table {$name} has no writable columns.");
        }

        foreach ($this->db->select(array_keys($writable))->from($name) as $row) {
            $values = [];
            foreach ($writable as $column => $type) {
                $values[] = $this->literal($row[$column], $type);
            }

            $this->writeSql(
                $stream,
                'INSERT INTO ' . $this->db->quoteName($name)
                . ' (' . implode(', ', array_map($this->db->quoteName(...), array_keys($writable))) . ')'
                . $this->insertValuesClause()
                . implode(', ', $values) . ')'
            );
        }

    }

    protected function insertValuesClause(): string
    {
        return ' VALUES (';
    }

    protected function literal(mixed $value, ?string $type = null): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
            if ($value === false) {
                throw new RuntimeException('Unable to read a database LOB.');
            }
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $this->db->quote($value);
    }

    protected function getCreateTable(string $table): string
    {
        throw new \LogicException('PHP schema export uses platform metadata.');
    }

    protected function getInserts(string $table): string
    {
        throw new \LogicException('PHP rows are exported incrementally.');
    }
}
