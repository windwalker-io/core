<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Migration\Repository;

use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Core\Database\Exporter\AbstractExporter;
use Windwalker\Core\Ioc;
use Windwalker\Core\Repository\Repository;
use Windwalker\Core\Repository\Traits\DatabaseModelTrait;
use Windwalker\Filesystem\File;
use Windwalker\Filesystem\Folder;
use Windwalker\Structure\Structure;

/**
 * The BackupModel class.
 *
 * @since  2.1.1
 */
class BackupRepository extends Repository
{
    use DatabaseModelTrait;

    /**
     * Property command.
     *
     * @var AbstractCommand
     */
    protected $command;

    /**
     * Property lastBackup.
     *
     * @var string
     */
    public $lastBackup;

    /**
     * Property exporter.
     *
     * @var  AbstractExporter
     */
    protected $exporter;

    /**
     * Instantiate the model.
     *
     * @param   Structure|array       $config   The model config.
     * @param   AbstractExporter|null $exporter SQL Exporter.
     *
     * @since   1.0
     */
    public function __construct($config = null, AbstractExporter $exporter = null)
    {
        parent::__construct($config);

        $this->exporter = $exporter;
    }

    /**
     * backup
     *
     * @return  boolean
     */
    public function backup()
    {
        $this->command->out()->out('Backing up SQL...');

        $config = Ioc::getConfig();
        $dir = $config->get('path.temp') . '/migration/sql-backup';

        Folder::create($dir);
        
        $this->rotate($dir);

        $file = $dir . '/sql-backup-' . gmdate('Y-m-d-H-i-s-') . uniqid() . '.sql';

        $this->exportTo($file);

        $this->lastBackup = $file;

        $this->command->out()->out('SQL backup to: <info>' . $file . '</info>')->out();

        return true;
    }

    /**
     * rotate
     *
     * @param string $dir
     *
     * @return  bool
     *
     * @since  3.4.2
     */
    protected function rotate($dir)
    {
        $files = Folder::files($dir);

        rsort($files);

        array_splice($files, 0, 20);

        return File::delete($files);
    }

    /**
     * getSQLExport
     *
     * @param  string  $file
     *
     * @return  void
     */
    public function exportTo(string $file)
    {
        Folder::create(dirname($file));

        $this->exporter->export($file);
    }

    /**
     * Method to get property Command
     *
     * @return  mixed
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Method to set property command
     *
     * @param   mixed $command
     *
     * @return  static  Return self to support chaining.
     */
    public function setCommand(AbstractCommand $command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * restoreLatest
     *
     * @return  void
     */
    public function restoreLatest()
    {
        $sql = $this->lastBackup;

        foreach ($this->db->splitSql($sql) as $query) {
            if (!trim($query)) {
                continue;
            }

            $this->db->setQuery($query)->execute();
        }

        $this->command->out('<info>Restore to latest backup complete.</info>');
    }
}
