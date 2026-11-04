<?php

declare(strict_types=1);

namespace Windwalker\Core\Database\Exporter;

use DomainException;
use Psr\Http\Message\StreamInterface;
use Windwalker\Database\Driver\DriverOptions;
use Windwalker\Stream\Stream;

use const Windwalker\Stream\WRITE_ONLY_RESET;

class SQLiteExporter extends AbstractCliExporter
{
    protected function options(): DriverOptions
    {
        $options = clone $this->db->getOptions();
        if ($options->dsn && str_starts_with($options->dsn, 'sqlite:')) {
            $options->file = substr($options->dsn, strlen('sqlite:'));
        }

        return $options;
    }

    protected function sqliteCommand(): ?array
    {
        $options = $this->options();
        $file = $options->file ?: $options->dbname;

        if (!$file || $file === ':memory:' || !is_file($file)) {
            return null;
        }

        $binary = $this->findExecutable('sqlite3', 'SQLITE3_BINARY');
        $path = realpath($file);
        if ($path === false) {
            throw new DomainException('SQLite CLI source file is no longer accessible.');
        }
        $path = str_replace('\\', '/', $path);
        $uri = 'file:' . str_replace(['%2F', '%3A'], ['/', ':'], rawurlencode($path)) . '?mode=ro';

        return $binary ? [$binary, '-batch', '-bail', $uri] : null;
    }

    protected function exportSqlCli(string $file): ?string
    {
        $command = $this->sqliteCommand();
        if ($command === null) {
            return null;
        }

        $stream = Stream::fromFilePath($file, WRITE_ONLY_RESET);
        try {
            $this->writeProcess($this->process([...$command, '.dump']), $stream);
        } finally {
            $stream->close();
        }

        return 'sql';
    }

    protected function exportNativeCli(string $file): ?string
    {
        $command = $this->sqliteCommand();
        if ($command === null) {
            return null;
        }

        $escaped = str_replace("'", "''", $file);
        $this->process([...$command, ".backup '{$escaped}'"])->mustRun();

        return 'sqlite';
    }

    protected function exportPhp(StreamInterface $stream): void
    {
        $objects = $this->db->prepare(
            'SELECT type, name, sql FROM sqlite_master WHERE sql IS NOT NULL '
            . "AND name NOT LIKE 'sqlite_%' ORDER BY CASE type WHEN 'table' THEN 0 "
            . "WHEN 'index' THEN 1 WHEN 'view' THEN 2 ELSE 3 END, name"
        )->all();
        $this->writeSql($stream, 'PRAGMA foreign_keys=OFF');
        $this->writeSql($stream, 'BEGIN TRANSACTION');
        foreach ($objects as $object) {
            if ($object['type'] !== 'table') {
                continue;
            }
            if (str_starts_with(strtoupper(trim($object['sql'])), 'CREATE VIRTUAL')) {
                throw new DomainException('PHP SQLite export does not support virtual tables; use sqlite3 .dump.');
            }
            $this->writeSql($stream, $object['sql']);
            $this->writeTableRows($stream, $object['name']);
        }
        foreach ($objects as $object) {
            if ($object['type'] !== 'table') {
                $this->writeSql($stream, $object['sql']);
            }
        }
        $hasSequence = $this->db->prepare(
            "SELECT count(*) FROM sqlite_master WHERE name='sqlite_sequence'"
        )->result();
        if ($hasSequence) {
            $this->writeSql($stream, 'DELETE FROM sqlite_sequence');
            $this->writeTableRows($stream, 'sqlite_sequence');
        }
        $this->writeSql($stream, 'COMMIT');
        $this->writeSql($stream, 'PRAGMA foreign_keys=ON');
    }

    protected function writeTableRows(StreamInterface $stream, string $table, ?string $schema = null): void
    {
        $quoted = $this->db->quoteName($table, true);
        $columns = $this->db->prepare('PRAGMA table_xinfo(' . $quoted . ')')->all();
        $names = [];
        $expressions = [];
        foreach ($columns as $column) {
            if ((int) $column['hidden'] !== 0) {
                continue;
            }
            $name = $column['name'];
            $names[] = $this->db->quoteName($name, true);
            $quotedColumn = $this->db->quoteName($name, true);
            $expressions[] = "CASE WHEN typeof({$quotedColumn})='text' THEN "
                . "'CAST(X''' || hex({$quotedColumn}) || ''' AS TEXT)' ELSE quote({$quotedColumn}) END AS "
                . $this->db->quoteName($name, true);
        }
        if ($names === []) {
            throw new DomainException("SQLite table {$table} has no writable columns.");
        }
        foreach ($this->db->prepare('SELECT ' . implode(', ', $expressions) . ' FROM ' . $quoted) as $row) {
            $values = [];
            foreach ($columns as $column) {
                if ((int) $column['hidden'] === 0) {
                    $values[] = $row[$column['name']];
                }
            }
            $this->writeSql(
                $stream,
                'INSERT INTO ' . $quoted . ' (' . implode(', ', $names) . ') VALUES (' . implode(', ', $values) . ')'
            );
        }
    }
}
