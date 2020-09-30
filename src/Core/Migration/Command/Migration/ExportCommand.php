<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Migration\Command\Migration;

use Windwalker\Console\Prompter\BooleanPrompter;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Database\DatabaseService;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Ioc;
use Windwalker\Core\Migration\Command\MigrationCommandTrait;
use Windwalker\DI\Annotation\Inject;
use Windwalker\Filesystem\File;
use Windwalker\Filesystem\Folder;

/**
 * The MigrateCommand class.
 *
 * @since  2.0
 */
class ExportCommand extends CoreCommand
{
    use MigrationCommandTrait;

    /**
     * An enabled flag.
     *
     * @var bool
     */
    public static $isEnabled = true;

    /**
     * Console(Argument) name.
     *
     * @var  string
     */
    protected $name = 'export';

    /**
     * The command description.
     *
     * @var  string
     */
    protected $description = 'Migrate the database';

    /**
     * The usage to tell user how to use this command.
     *
     * @var string
     */
    protected $usage = 'export <cmd><dest></cmd> <option>[option]</option>';

    /**
     * Property databaseService.
     *
     * @Inject()
     *
     * @var DatabaseService
     */
    protected $databaseService;

    /**
     * Configure command information.
     *
     * @return void
     */
    public function init()
    {
        $default = Ioc::service(DatabaseService::class)->getDefaultConnectionName();

        $this->addOption('c')
            ->alias('connection')
            ->description(sprintf('Connection to export, default is `%s`.', $default))
            ->defaultValue($default);
    }

    /**
     * Prepare execute hook.
     *
     * @return  void
     */
    protected function prepareExecute()
    {
        parent::prepareExecute();
    }

    /**
     * Execute this command.
     *
     * @return int|void
     * @throws \Exception
     */
    protected function doExecute()
    {
        $conn = $this->getOption('c');

        $dest = $this->getArgument(0) ?: sprintf(
            WINDWALKER_TEMP . '/sql-export/sql-%s-%s.sql',
            $conn,
            Chronos::create()->format('Y-m-d-H-i-s')
        );

        Folder::create(dirname($dest));

        $repository = $this->getBackupRepository(
            Ioc::service(DatabaseService::class)->getConnection($conn)
        );
        $repository->exportTo($dest);

        $this->out(sprintf('Export SQL to: <info>%s</info>', $dest));

        return true;
    }
}
