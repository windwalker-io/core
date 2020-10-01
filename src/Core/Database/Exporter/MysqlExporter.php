<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Database\Exporter;

use Symfony\Component\Process\Process;
use Windwalker\Core\Database\TableHelper;
use Windwalker\Environment\PlatformHelper;
use Windwalker\Http\Stream\Stream;
use Windwalker\Query\Mysql\MysqlGrammar;

/**
 * The Exporter class.
 *
 * @since  2.1.1
 */
class MysqlExporter extends AbstractExporter
{
    /**
     * export
     *
     * @param  string  $file
     *
     * @return void
     */
    public function export(string $file)
    {
        $md = trim($this->findMysqldump());

        if ($md && class_exists(Process::class)) {
            $options = $this->db->getOptions();

            $process = Process::fromShellCommandline(
                sprintf(
                    '%s -u %s -p%s %s > %s',
                    $md,
                    $options['user'],
                    $options['password'],
                    $options['database'],
                    $file
                )
            );

            $process->setTimeout(600);
            $process->mustRun();

            return;
        }

        $stream = new Stream($file, Stream::MODE_WRITE_ONLY_RESET);

        $tables = $this->db->getDatabase()->getTables(true);

        foreach ($tables as $table) {
            $sql = [];

            // Table
            $sql[] = MysqlGrammar::dropTable($table, true);
            $sql[] = $this->getCreateTable($table);

            $stream->write((string) implode(";\n", $sql));

            // Data
            $inserts = $this->getInserts($table);

            if ($inserts) {
                $stream->write((string) $inserts);
            }
        }

        $stream->close();
    }

    /**
     * findMysqldump
     *
     * @return  string
     *
     * @since  3.5.22
     */
    protected function findMysqldump(): ?string
    {
        if ($md = env('MYSQLDUMP_BINARY')) {
            return $md;
        }

        if (class_exists(Process::class)) {
            $process = Process::fromShellCommandline('which mysqldump');
            $code = $process->run();

            if ($code === 0) {
                return $process->getOutput();
            }
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
    protected function getCreateTable($table)
    {
        $db = $this->db;

        $result = $db->getReader('SHOW CREATE TABLE ' . $this->db->quoteName($table))->loadArray();

        $sql = preg_replace('#AUTO_INCREMENT=\S+#is', '', $result[1]);

        $sql = explode("\n", $sql);

        $tableStriped = TableHelper::stripPrefix($result[0], $db->getPrefix());

        $sql[0] = str_replace($result[0], $tableStriped, $sql[0]);

        $sql = implode("\n", $sql);

        return $sql;
    }

    /**
     * getInserts
     *
     * @param $table
     *
     * @return mixed|null|string
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function getInserts($table)
    {
        $db       = $this->db;
        $query    = $db->getQuery(true);
        $iterator = $db->getReader($query->select('*')->from($query->quoteName($table)))->getIterator();

        if (!count($iterator)) {
            return null;
        }

        $sql = [];

        foreach ($iterator as $data) {
            $data = (array) $data;

            $data = array_map(
                function ($d) use ($query) {
                    return $query->q($d) ?: 'NULL';
                },
                $data
            );

            $value = implode(', ', $data);

            $sql[] = (string) sprintf("INSERT `%s` VALUES (%s)", $table, $value);
        }

        return (string) implode(";\n", $sql);
    }
}
