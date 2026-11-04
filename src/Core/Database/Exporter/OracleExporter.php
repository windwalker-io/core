<?php

declare(strict_types=1);

namespace Windwalker\Core\Database\Exporter;

use DomainException;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessException;
use Windwalker\Database\Manager\TableManager;
use Windwalker\Stream\Stream;

use const Windwalker\Stream\WRITE_ONLY_RESET;

class OracleExporter extends AbstractCliExporter
{
    protected function connection(): string
    {
        $options = $this->options();

        if ($options->dsn && $options->dbname) {
            return $options->dbname;
        }

        return '//' . ($options->host ?: 'localhost') . ':' . ($options->port ?: 1521)
            . '/' . $options->dbname;
    }

    protected function login(): string
    {
        $options = $this->options();
        foreach ([$options->user, $options->password, $this->connection()] as $value) {
            if (str_contains((string) $value, "\n") || str_contains((string) $value, "\r")) {
                throw new DomainException('Oracle CLI connection values cannot contain line breaks.');
            }
        }

        return 'CONNECT "' . str_replace('"', '""', $options->user ?? '')
            . '"/"' . str_replace('"', '""', $options->password ?? '') . '"@' . $this->connection();
    }

    protected function exportSqlCli(string $file): ?string
    {
        $binary = $this->findExecutable('sql', 'SQLCL_BINARY');
        if ($binary === null) {
            return null;
        }

        $script = "WHENEVER SQLERROR EXIT FAILURE\nWHENEVER OSERROR EXIT FAILURE\nSET DEFINE OFF\n"
            . $this->login() . "\nSET HEADING OFF\nSET FEEDBACK OFF\nSET PAGESIZE 0\n"
            . "SET LONG 1000000000\nSET LONGCHUNKSIZE 32767\nSET LINESIZE 32767\n"
            . "EXEC DBMS_METADATA.SET_TRANSFORM_PARAM(DBMS_METADATA.SESSION_TRANSFORM,'SQLTERMINATOR',TRUE);\n"
            . "EXEC DBMS_METADATA.SET_TRANSFORM_PARAM(DBMS_METADATA.SESSION_TRANSFORM,'REF_CONSTRAINTS',FALSE);\n";
        $tables = $this->db->getSchemaManager()->getTables();
        $objects = $this->schemaObjects();
        foreach ($objects['SEQUENCE'] as $name) {
            $script .= "SELECT DBMS_METADATA.GET_DDL('SEQUENCE', " . $this->db->quote($name) . ") FROM DUAL;\n";
        }
        foreach ($tables as $table) {
            $this->assertSupportedTable($table->tableName);
            $name = $this->db->quote(strtoupper($table->tableName));
            $script .= "SET SQLFORMAT DEFAULT\nSELECT DBMS_METADATA.GET_DDL('TABLE', {$name}) FROM DUAL;\n";
        }
        foreach ($tables as $table) {
            $columns = $this->writableColumns($this->db->getTableManager($table->tableName, true));
            $names = array_map(fn($column) => $this->db->quoteName($column->getColumnName(), true), $columns);
            $script .= "SET SQLFORMAT INSERT\nSELECT " . implode(', ', $names) . ' FROM '
                . $this->db->quoteName($table->tableName, true) . ";\n";
        }
        foreach ($tables as $table) {
            $script .= "SET SQLFORMAT DEFAULT\n"
                . "SELECT DBMS_METADATA.GET_DEPENDENT_DDL('REF_CONSTRAINT', "
                . $this->db->quote(strtoupper($table->tableName)) . ') FROM DUAL WHERE EXISTS '
                . "(SELECT 1 FROM USER_CONSTRAINTS WHERE CONSTRAINT_TYPE='R' AND TABLE_NAME="
                . $this->db->quote(strtoupper($table->tableName)) . ");\n";
        }
        foreach (['INDEX', 'VIEW', 'TRIGGER'] as $type) {
            foreach ($objects[$type] as $name) {
                $script .= "SET SQLFORMAT DEFAULT\nSELECT DBMS_METADATA.GET_DDL('{$type}', "
                    . $this->db->quote($name) . ") FROM DUAL;\n";
            }
        }
        $script .= "COMMIT;\nEXIT SUCCESS\n";
        $stream = Stream::fromFilePath($file, WRITE_ONLY_RESET);
        try {
            $this->writeProcess($this->process([$binary, '-s', '/nolog'], input: $script), $stream);
        } finally {
            $stream->close();
        }

        return 'sql';
    }

    protected function exportNativeCli(string $file): ?string
    {
        $directory = $this->app->config('database.export.oracle.directory');
        $localDirectory = $this->app->config('database.export.oracle.local_directory');
        $dataPump = $this->findExecutable('expdp', 'ORACLE_EXPDP_BINARY');
        if ($dataPump && is_string($directory) && is_string($localDirectory) && is_dir($localDirectory)) {
            $filename = 'windwalker-' . bin2hex(random_bytes(12)) . '.dmp';
            $artifact = $localDirectory . DIRECTORY_SEPARATOR . $filename;
            try {
                if (!preg_match('/^[A-Za-z][A-Za-z0-9_$#]*$/D', $directory)) {
                    throw new DomainException('Invalid Oracle Data Pump directory object.');
                }
                $options = $this->options();
                $process = $this->process(
                    [
                        $dataPump, ($options->user ?? '') . '@' . $this->connection(),
                        'DIRECTORY=' . $directory, 'DUMPFILE=' . $filename,
                        'SCHEMAS=' . $options->user, 'NOLOGFILE=YES',
                    ],
                    input: ($options->password ?? '') . "\n"
                )->mustRun();
                $this->assertNoOracleErrors($process);
                if (!is_file($artifact) || !copy($artifact, $file)) {
                    throw new DomainException('Data Pump dump is not readable through the local directory mapping.');
                }

                return 'dmp';
            } catch (ProcessException|DomainException $e) {
                $this->emitMessage('Oracle Data Pump failed; trying local legacy exp.');
            } finally {
                if (is_file($artifact) && !unlink($artifact)) {
                    throw new \RuntimeException('Unable to remove the mapped Data Pump scratch file.');
                }
            }
        }

        $binary = $this->findExecutable('exp', 'ORACLE_EXP_BINARY');
        if ($binary === null) {
            return null;
        }

        $options = $this->options();
        $artifact = $file . '.dmp';
        try {
            $process = $this->process(
                [
                    $binary, ($options->user ?? '') . '@' . $this->connection(),
                    'FILE=' . $artifact, 'LOG=' . $artifact . '.log',
                    'OWNER=' . $options->user, 'CONSISTENT=Y',
                ],
                input: ($options->password ?? '') . "\n"
            )->mustRun();
            $this->assertNoOracleErrors($process);
            if (!is_file($artifact) || !copy($artifact, $file)) {
                throw new DomainException('Oracle exp did not produce a readable local dump.');
            }
        } finally {
            foreach ([$artifact, $artifact . '.log'] as $temporary) {
                if (is_file($temporary) && !unlink($temporary)) {
                    throw new \RuntimeException('Unable to remove the Oracle export scratch file.');
                }
            }
        }

        return 'dmp';
    }

    protected function assertNoOracleErrors(\Symfony\Component\Process\Process $process): void
    {
        if (preg_match('/\b(?:ORA|EXP|UDE|LRM)-\d{4,5}\b/', $process->getOutput() . $process->getErrorOutput())) {
            throw new DomainException('Oracle export reported database errors despite a successful exit status.');
        }
    }

    protected function exportPhp(StreamInterface $stream): void
    {
        $this->writeSql($stream, 'SET DEFINE OFF');
        $this->db->execute(
            "BEGIN DBMS_METADATA.SET_TRANSFORM_PARAM(DBMS_METADATA.SESSION_TRANSFORM,'REF_CONSTRAINTS',FALSE); END;"
        );
        try {
            $tables = $this->db->getSchemaManager()->getTables();
            $objects = $this->schemaObjects();
            foreach ($objects['SEQUENCE'] as $name) {
                $this->writeSql($stream, $this->metadata(
                    "DBMS_METADATA.GET_DDL('SEQUENCE', " . $this->db->quote($name) . ')'
                ));
            }
            foreach ($tables as $table) {
                $this->assertSupportedTable($table->tableName);
                $this->writeSql($stream, $this->metadata(
                    "DBMS_METADATA.GET_DDL('TABLE', "
                    . $this->db->quote(strtoupper($table->tableName)) . ')'
                ));
            }
            foreach ($tables as $table) {
                $this->writeTableRows($stream, $table->tableName);
            }
            foreach ($tables as $table) {
                $hasReferences = $this->db->prepare(
                    "SELECT count(*) FROM USER_CONSTRAINTS WHERE CONSTRAINT_TYPE='R' AND TABLE_NAME="
                    . $this->db->quote(strtoupper($table->tableName))
                )->result();
                if ($hasReferences) {
                    $this->writeSql($stream, $this->metadata(
                        "DBMS_METADATA.GET_DEPENDENT_DDL('REF_CONSTRAINT', "
                        . $this->db->quote(strtoupper($table->tableName)) . ')'
                    ));
                }
            }
            foreach (['INDEX', 'VIEW', 'TRIGGER'] as $type) {
                foreach ($objects[$type] as $name) {
                    $this->writeSql($stream, $this->metadata(
                        "DBMS_METADATA.GET_DDL('{$type}', " . $this->db->quote($name) . ')'
                    ));
                }
            }
            $this->writeSql($stream, 'COMMIT');
        } finally {
            $this->db->execute(
                "BEGIN DBMS_METADATA.SET_TRANSFORM_PARAM(DBMS_METADATA.SESSION_TRANSFORM,'REF_CONSTRAINTS',TRUE); END;"
            );
        }
    }

    protected function assertSupportedTable(string $table): void
    {
        $unsupported = $this->db->prepare(
            "SELECT count(*) FROM USER_TAB_IDENTITY_COLS WHERE GENERATION_TYPE='ALWAYS' AND TABLE_NAME="
            . $this->db->quote(strtoupper($table))
        )->result();
        if ($unsupported) {
            throw new DomainException('Oracle SQL export requires native tools for GENERATED ALWAYS identity columns.');
        }
    }

    protected function schemaObjects(): array
    {
        $objects = ['SEQUENCE' => [], 'INDEX' => [], 'VIEW' => [], 'TRIGGER' => []];
        foreach ($this->db->prepare(
            "SELECT OBJECT_NAME, OBJECT_TYPE FROM USER_OBJECTS WHERE OBJECT_TYPE IN ('SEQUENCE','INDEX','VIEW','TRIGGER') "
            . "AND GENERATED='N' AND NOT (OBJECT_TYPE='INDEX' AND OBJECT_NAME IN "
            . '(SELECT INDEX_NAME FROM USER_CONSTRAINTS WHERE INDEX_NAME IS NOT NULL)) ORDER BY CREATED, OBJECT_NAME'
        ) as $row) {
            $objects[$row['object_type']][] = $row['object_name'];
        }

        return $objects;
    }

    protected function writableColumns(TableManager $manager): array
    {
        $virtual = $this->db->prepare(
            "SELECT LOWER(COLUMN_NAME) FROM USER_TAB_COLS WHERE VIRTUAL_COLUMN='YES' AND TABLE_NAME="
            . $this->db->quote(strtoupper($manager->getName()))
        )->loadColumn()->dump();

        return array_filter(
            parent::writableColumns($manager),
            static fn($column) => !in_array($column->getColumnName(), $virtual, true)
        );
    }

    protected function writeTableRows(StreamInterface $stream, string $table, ?string $schema = null): void
    {
        $columns = $this->db->prepare(
            "SELECT COLUMN_NAME, DATA_TYPE FROM USER_TAB_COLS WHERE HIDDEN_COLUMN='NO' AND VIRTUAL_COLUMN='NO' "
            . 'AND TABLE_NAME=' . $this->db->quote(strtoupper($table)) . ' ORDER BY COLUMN_ID'
        )->all();
        $names = [];
        $expressions = [];
        foreach ($columns as $column) {
            $name = $this->db->quoteName($column['column_name'], true);
            $type = strtoupper($column['data_type']);
            if ($type === 'DATE' || str_starts_with($type, 'TIMESTAMP')) {
                if (str_contains($type, 'TIME ZONE')) {
                    throw new DomainException('PHP Oracle export requires native tools for time-zone timestamps.');
                }
                $format = $type === 'DATE' ? 'YYYY-MM-DD HH24:MI:SS' : 'YYYY-MM-DD HH24:MI:SS.FF9';
                $expressions[] = "TO_CHAR({$name}, '{$format}') AS {$name}";
            } else {
                $expressions[] = $name;
            }
            $names[] = $name;
        }
        if ($names === []) {
            throw new DomainException("Oracle table {$table} has no writable columns.");
        }
        $quoted = $this->db->quoteName($table, true);
        foreach ($this->db->prepare('SELECT ' . implode(', ', $expressions) . ' FROM ' . $quoted) as $row) {
            $values = [];
            foreach ($columns as $column) {
                $values[] = $this->literal($row[strtolower($column['column_name'])], $column['data_type']);
            }
            $this->writeSql(
                $stream,
                'INSERT INTO ' . $quoted . ' (' . implode(', ', $names) . ') VALUES (' . implode(', ', $values) . ')'
            );
        }
    }

    protected function metadata(string $expression): string
    {
        $value = $this->db->prepare('SELECT ' . $expression . ' FROM DUAL')->result();
        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }
        if (!is_string($value) || trim($value) === '') {
            throw new DomainException('Oracle DBMS_METADATA did not return DDL.');
        }

        return $value;
    }

    protected function literal(mixed $value, ?string $type = null): string
    {
        if ($value === null) {
            return 'NULL';
        }
        $type = strtoupper($type ?? '');
        if (is_resource($value)) {
            $value = stream_get_contents($value);
            if ($value === false) {
                throw new \RuntimeException('Unable to read Oracle LOB data.');
            }
        }
        if (in_array($type, ['BLOB', 'RAW', 'LONG RAW'], true)) {
            if (!is_string($value) || strlen($value) > 2000) {
                throw new DomainException('PHP Oracle export requires native tools for binary values over 2000 bytes.');
            }

            return "HEXTORAW('" . bin2hex($value) . "')";
        }
        if (is_string($value) && strlen($value) > 4000) {
            throw new DomainException('PHP Oracle export requires native tools for text values over 4000 bytes.');
        }
        if ($type === 'DATE') {
            return 'TO_DATE(' . $this->db->quote($value) . ", 'YYYY-MM-DD HH24:MI:SS')";
        }
        if (str_starts_with($type, 'TIMESTAMP')) {
            return 'TO_TIMESTAMP(' . $this->db->quote($value) . ", 'YYYY-MM-DD HH24:MI:SS.FF')";
        }

        return parent::literal($value, $type);
    }
}
