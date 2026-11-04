<?php

declare(strict_types=1);

namespace Windwalker\Core\Database\Exporter;

use Psr\Http\Message\StreamInterface;
use Windwalker\Database\Manager\TableManager;

/**
 * The Exporter class.
 *
 * @since  3.5
 */
class SqlsrvExporter extends AbstractCliExporter
{
    protected function writeSchema(StreamInterface $stream, string $schema): void
    {
        $this->writeSql(
            $stream,
            'IF SCHEMA_ID(' . $this->db->quote($schema) . ') IS NULL EXEC('
            . $this->db->quote('CREATE SCHEMA ' . $this->db->quoteName($schema, true)) . ')'
        );
    }

    protected function tableDdl(TableManager $manager): array
    {
        $name = ($manager->schemaName ?: 'dbo') . '.' . $manager->getName();
        $rows = $this->db->prepare(
            'SELECT c.name, t.name AS type, c.max_length, c.precision, c.scale, c.is_nullable, '
            . 'c.collation_name, d.definition AS default_expression, cc.definition AS computed_expression, '
            . 'cc.is_persisted, i.seed_value, i.increment_value '
            . 'FROM sys.columns c JOIN sys.types t ON c.user_type_id=t.user_type_id '
            . 'LEFT JOIN sys.default_constraints d ON c.default_object_id=d.object_id '
            . 'LEFT JOIN sys.computed_columns cc ON c.object_id=cc.object_id AND c.column_id=cc.column_id '
            . 'LEFT JOIN sys.identity_columns i ON c.object_id=i.object_id AND c.column_id=i.column_id '
            . 'WHERE c.object_id=OBJECT_ID(' . $this->db->quote($name) . ') ORDER BY c.column_id'
        )->all();
        $columns = [];
        foreach ($rows as $row) {
            $definition = $this->db->quoteName($row['name'], true);
            if ($row['computed_expression'] !== null) {
                $definition .= ' AS ' . $row['computed_expression'] . ($row['is_persisted'] ? ' PERSISTED' : '');
            } else {
                $type = strtolower($row['type']);
                $definition .= ' ' . $this->db->quoteName($row['type'], true);
                if (in_array($type, ['varchar', 'char', 'binary', 'varbinary', 'nvarchar', 'nchar'], true)) {
                    $length = (int) $row['max_length'];
                    if ($length !== -1 && str_starts_with($type, 'n')) {
                        $length = intdiv($length, 2);
                    }
                    $definition .= '(' . ($length === -1 ? 'MAX' : $length) . ')';
                } elseif (in_array($type, ['numeric', 'decimal'], true)) {
                    $definition .= '(' . (int) $row['precision'] . ', ' . (int) $row['scale'] . ')';
                } elseif (in_array($type, ['datetime2', 'datetimeoffset', 'time'], true)) {
                    $definition .= '(' . (int) $row['scale'] . ')';
                }
                if ($row['collation_name']) {
                    if (!preg_match('/^[A-Za-z0-9_]+$/D', $row['collation_name'])) {
                        throw new \DomainException('Unsupported SQL Server collation identifier.');
                    }
                    $definition .= ' COLLATE ' . $row['collation_name'];
                }
                if ($row['seed_value'] !== null) {
                    $definition .= ' IDENTITY(' . $row['seed_value'] . ', ' . $row['increment_value'] . ')';
                }
                $definition .= $row['is_nullable'] ? ' NULL' : ' NOT NULL';
                if ($row['default_expression'] !== null) {
                    $definition .= ' DEFAULT ' . $row['default_expression'];
                }
            }
            $columns[] = $definition;
        }
        if ($columns === []) {
            throw new \DomainException("No SQL Server column metadata for {$name}.");
        }

        return ['CREATE TABLE ' . $this->db->quoteName($name)
            . " (\n" . implode(",\n", $columns) . "\n)"];
    }

    protected function writableColumns(TableManager $manager): array
    {
        $name = ($manager->schemaName ?: 'dbo') . '.' . $manager->getName();
        $excluded = $this->db->prepare(
            'SELECT name FROM sys.columns WHERE object_id=OBJECT_ID(' . $this->db->quote($name)
            . ') AND (is_computed=1 OR system_type_id=189)'
        )->loadColumn()->dump();

        return array_filter(
            parent::writableColumns($manager),
            static fn($column) => !in_array($column->getColumnName(), $excluded, true)
        );
    }

    protected function tablePostDdl(TableManager $manager): array
    {
        [$local] = parent::tablePostDdl($manager);
        $name = ($manager->schemaName ?: 'dbo') . '.' . $manager->getName();
        $keys = [];
        foreach ($this->db->prepare(
            'SELECT f.object_id, f.name, f.update_referential_action_desc AS update_action, '
            . 'f.delete_referential_action_desc AS delete_action, '
            . 'OBJECT_SCHEMA_NAME(f.referenced_object_id) AS referenced_schema, '
            . 'OBJECT_NAME(f.referenced_object_id) AS referenced_table, '
            . 'COL_NAME(k.parent_object_id,k.parent_column_id) AS column_name, '
            . 'COL_NAME(k.referenced_object_id,k.referenced_column_id) AS referenced_column '
            . 'FROM sys.foreign_keys f JOIN sys.foreign_key_columns k ON k.constraint_object_id=f.object_id '
            . 'WHERE f.parent_object_id=OBJECT_ID(' . $this->db->quote($name) . ') '
            . 'ORDER BY f.object_id,k.constraint_column_id'
        ) as $row) {
            $id = $row['object_id'];
            $keys[$id] ??= [
                'name' => $row['name'],
                'table' => $row['referenced_schema'] . '.' . $row['referenced_table'],
                'update' => str_replace('_', ' ', $row['update_action']),
                'delete' => str_replace('_', ' ', $row['delete_action']),
                'columns' => [],
                'references' => [],
            ];
            $keys[$id]['columns'][] = $this->db->quoteName($row['column_name'], true);
            $keys[$id]['references'][] = $this->db->quoteName($row['referenced_column'], true);
        }
        $references = [];
        foreach ($keys as $key) {
            $references[] = 'ALTER TABLE ' . $this->db->quoteName($name)
                . ' ADD CONSTRAINT ' . $this->db->quoteName($key['name'], true)
                . ' FOREIGN KEY (' . implode(', ', $key['columns']) . ') REFERENCES '
                . $this->db->quoteName($key['table']) . ' (' . implode(', ', $key['references']) . ')'
                . ' ON UPDATE ' . $key['update'] . ' ON DELETE ' . $key['delete'];
        }

        return [$local, $references];
    }

    protected function server(): string
    {
        $options = $this->options();

        $host = preg_replace('/^tcp:/i', '', $options->host ?: 'localhost');

        return $host . ($options->port && !str_contains($host, ',') ? ',' . $options->port : '');
    }

    protected function exportSqlCli(string $file): ?string
    {
        $binary = $this->findExecutable('mssql-scripter', 'MSSQL_SCRIPTER_BINARY');
        if ($binary === null) {
            return null;
        }

        $options = $this->options();
        $command = [
            $binary, '-S', $this->server(), '-d', $options->dbname,
            '--schema-and-data', '--file-path', $file,
        ];
        if ($options->user) {
            array_push($command, '-U', $options->user);
        }
        $this->process($command, ['MSSQL_SCRIPTER_PASSWORD' => $options->password ?? ''])->mustRun();

        return 'sql';
    }

    protected function exportNativeCli(string $file): ?string
    {
        $binary = $this->findExecutable('sqlpackage', 'SQLPACKAGE_BINARY');
        if ($binary === null) {
            return null;
        }

        $options = $this->options();
        $artifact = $file . '.bacpac';
        $command = [
            $binary, '/Action:Export', '/TargetFile:' . $artifact,
            '/SourceServerName:' . $this->server(), '/SourceDatabaseName:' . $options->dbname,
        ];
        if ($options->user) {
            $command[] = '/SourceUser:' . $options->user;
            $command[] = '/SourcePassword:' . ($options->password ?? '');
        }
        try {
            $this->process($command)->mustRun();
            if (!is_file($artifact) || !copy($artifact, $file)) {
                throw new \DomainException('SQLPackage did not produce a readable local BACPAC.');
            }
        } finally {
            if (is_file($artifact) && !unlink($artifact)) {
                throw new \RuntimeException('Unable to remove the SQLPackage export scratch file.');
            }
        }

        return 'bacpac';
    }

    protected function writeTableRows(StreamInterface $stream, string $table, ?string $schema = null): void
    {
        $manager = $this->db->getTableManager($table, true);
        $manager->schemaName = $schema;
        $identity = false;
        foreach ($manager->getColumns() as $column) {
            $identity = $identity || $column->isAutoIncrement();
        }
        $quoted = $this->db->quoteName($schema ? $schema . '.' . $table : $table);
        if ($identity) {
            $this->writeSql($stream, 'SET IDENTITY_INSERT ' . $quoted . ' ON');
        }
        parent::writeTableRows($stream, $table, $schema);
        if ($identity) {
            $this->writeSql($stream, 'SET IDENTITY_INSERT ' . $quoted . ' OFF');
            $lastValue = $this->db->prepare(
                'SELECT last_value FROM sys.identity_columns WHERE object_id=OBJECT_ID('
                . $this->db->quote($schema ? $schema . '.' . $table : $table) . ')'
            )->result();
            if ($lastValue !== null) {
                $this->writeSql(
                    $stream,
                    'DBCC CHECKIDENT (' . $this->db->quote($quoted) . ', RESEED, ' . $lastValue . ')'
                );
            }
        }
    }

    protected function literal(mixed $value, ?string $type = null): string
    {
        if ($value !== null && in_array(strtolower($type ?? ''), ['binary', 'varbinary', 'image'], true)) {
            $bytes = is_resource($value) ? stream_get_contents($value) : $value;
            if (!is_string($bytes)) {
                throw new \RuntimeException('Unable to read SQL Server binary data.');
            }

            return '0x' . bin2hex($bytes);
        }

        return is_string($value) ? 'N' . parent::literal($value, $type) : parent::literal($value, $type);
    }
}
