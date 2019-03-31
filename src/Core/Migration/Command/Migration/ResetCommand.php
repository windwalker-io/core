<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Migration\Command\Migration;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Migration\Command\MigrationCommandTrait;

/**
 * The CreateCommand class.
 *
 * @since  2.0
 */
class ResetCommand extends CoreCommand
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
    protected $name = 'reset';

    /**
     * The command description.
     *
     * @var  string
     */
    protected $description = 'Reset all migrations.';

    /**
     * The usage to tell user how to use this command.
     *
     * @var string
     */
    protected $usage = 'create <cmd><command></cmd> <option>[option]</option>';

    /**
     * Configure command information.
     *
     * @return void
     */
    public function init()
    {
        $this->addOption('s')
            ->alias('seed')
            ->description('Also import seeds.');

        $this->addOption('no-backup')
            ->description('Do not backup database.');
    }

    /**
     * Prepare execute hook.
     *
     * @return  void
     */
    protected function prepareExecute()
    {
        $this->createDatabase();

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
        if ($this->console->getMode() !== 'dev') {
            throw new \RuntimeException(
                '<error>STOP!</error> <comment>please set env WINDWALKER_MODE=dev</comment>.'
            );
        }

        // backup
        if (!$this->getOption('no-backup')) {
            $this->backup();
        }

        $this->out()->out('<cmd>Rollback to 0 version...</cmd>');

        $this->executeCommand(['migration', 'migrate', '0']);

        $this->out()->out('<cmd>Migrating to latest version...</cmd>');

        $this->executeCommand(['migration', 'migrate']);

        return true;
    }

    /**
     * executeCommand
     *
     * @param array $args
     *
     * @return  boolean
     * @throws \Exception
     */
    protected function executeCommand($args)
    {
        $io = clone $this->io;

        $io->setArguments($args);
        $io->setOption('no-backup', true);
        $io->setOption('no-create-database', true);

//        foreach ($this->io->getOptions() as $k => $v)
//        {
//            $io->setOption($k, $v);
//        }

        return $this->console->getRootCommand()->setIO($io)->execute();
    }
}
