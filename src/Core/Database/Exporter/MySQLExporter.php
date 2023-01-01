<?php

/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

declare(strict_types=1);

namespace Windwalker\Core\Database\Exporter;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Windwalker\Core\Database\DatabaseExportService;
use Windwalker\Environment\PlatformHelper;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Stream\Stream;

/**
 * The Exporter class.
 *
 * @since  2.1.1
 */
class MySQLExporter extends AbstractExporter
{
    /**
     * export
     *
     * @param  StreamInterface  $stream
     * @param  array            $options
     *
     * @return void
     */
    protected function doExport(StreamInterface $stream, array $options = []): void
    {
        $md = trim($this->findMysqldump());

        $gzMode = $options['gz'] ?? null;

        $dbOptions = $this->db->getDriver()->getOptions();
        $cmd = sprintf(
            '%s --defaults-extra-file=%s %s %s',
            $md,
            $this->createPasswordCnfFile($dbOptions),
            $dbOptions['dbname'],
            env('MYSQLDUMP_EXTRA_OPTIONS') ?? '',
        );

        if ($gzMode === 'cli') {
            $cmd .= ' | gzip';
        }

        $process = $this->app->createProcess($cmd);

        try {
            $process->mustRun(
                function ($type, $buffer) use ($stream) {
                    $stream->write($buffer);
                }
            );
        } catch (ProcessFailedException $e) {
            $this->emitMessage(
                [
                    'Error: ' . $e->getMessage(),
                    'Fallback to php backup script.',
                ]
            );

            $tableInfos = $this->db->getSchema()->getTables(true);

            foreach ($tableInfos as $tableInfo) {
                $sql = [];

                // Table
                $sql[] = "DROP TABLE `{$tableInfo->tableName}` IF EXISTS";
                $sql[] = $this->getCreateTable($tableInfo->tableName);

                $stream->write((string) implode(";\n", $sql));

                // Data
                $inserts = $this->getInserts($tableInfo->tableName);

                if ($inserts) {
                    $stream->write((string) $inserts);
                }
            }
        }

        $stream->close();
    }

    protected function createPasswordCnfFile(array $options): string
    {
        $tmpFile = $this->app->path('@temp/.md.cnf');

        $user = addslashes($options['user'] ?? 'root');
        $password = addslashes($options['password'] ?? '');
        $host = addslashes($options['host'] ?? 'localhost');
        $port = addslashes($options['port'] ?? '3306');

        $content = <<<CNF
[mysqldump]
user='$user'
password='$password'
host='$host'
port='$port'
CNF;

        Filesystem::write($tmpFile, $content);

        register_shutdown_function(
            static function () use ($tmpFile) {
                Filesystem::delete($tmpFile);
            }
        );

        return $tmpFile;
    }

    /**
     * findMysqldump
     *
     * @return string|null
     *
     * @since  3.5.22
     */
    protected function findMysqldump(): ?string
    {
        if ($md = env('MYSQLDUMP_BINARY')) {
            return $md;
        }

        $process = $this->app->runProcess('which mysqldump');

        if ($process->isSuccessful()) {
            return $process->getOutput();
        }

        $pos = [];

        if (PlatformHelper::isWindows()) {
            $pos = [
                'C:\xampp\mysql\bin\mysqldump.exe',
            ];
        } elseif (PlatformHelper::isUnix()) {
            $pos = [
                '/Applications/XAMPP/xamppfiles/bin/mysqldump',
                '/Applications/AMPPS/bin/mysqldump',
            ];
        }

        foreach ($pos as $md) {
            if (is_file($md) && is_executable($md)) {
                return $md;
            }
        }

        return null;
    }

    /**
     * getCreateTable
     *
     * @param $table
     *
     * @return array|mixed|string
     */
    protected function getCreateTable(string $table): string
    {
        $db = $this->db;

        $result = $db->prepare('SHOW CREATE TABLE ' . $this->db->quoteName($table))
            ->get()
            ->values();

        $sql = preg_replace('#AUTO_INCREMENT=\S+#is', '', $result[1]);

        $sql = explode("\n", $sql);

        $tableStriped = DatabaseExportService::stripPrefix($result[0], $db->getDriver()->getOption('prefix'));

        $sql[0] = str_replace($result[0], $tableStriped, $sql[0]);

        $sql = implode("\n", $sql);

        return $sql;
    }

    /**
     * getInserts
     *
     * @param  string  $table
     *
     * @return string
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function getInserts(string $table): string
    {
        $db = $this->db;
        $query = $db->select('*')->from($table);

        $sql = [];

        foreach ($query as $data) {
            $data = (array) $data->dump();

            $data = array_map(
                static function ($d) use ($query) {
                    return $query->q($d) ?: 'NULL';
                },
                $data
            );

            $value = implode(', ', $data);

            $sql[] = (string) sprintf("INSERT `%s` VALUES (%s)", $table, $value);
        }

        if ($sql === []) {
            return '';
        }

        return (string) implode(";\n", $sql);
    }
}
