<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Seeder\Command\Seed;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Migration\Command\MigrationCommandTrait;
use Windwalker\Core\Migration\Repository\BackupRepository;
use Windwalker\Database\Driver\Mysql\MysqlDriver;

/**
 * Class Seed
 */
class ImportCommand extends CoreCommand
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
    protected $name = 'import';

    /**
     * The command description.
     *
     * @var  string
     */
    protected $description = 'Import seeders.';

    /**
     * The usage to tell user how to use this command.
     *
     * @var string
     */
    protected $usage = 'import <cmd><command></cmd> <option>[option]</option>';

    /**
     * Initialise command information.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->addOption('no-backup')
            ->description('Do not backup database.');
    }

    /**
     * Execute this command.
     *
     * @return bool
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    protected function doExecute()
    {
        if ($this->console->getMode() !== 'dev') {
            $this->console->out(
                '<error>STOP!</error> please run: <info>' . $this->getEnvCmd() . '</info>.'
            )->close();
        }

        // backup
        if (!$this->getOption('no-backup')) {
            BackupRepository::getInstance()->setCommand($this)->backup();
        }

        $class = $this->console->get('seed.class');

        /** @var \Windwalker\Core\Seeder\AbstractSeeder $seeder */
        $seeder = $this->console->container->newInstance($class, ['command' => $this]);

        $db = $this->console->database;
        $tables = $db->getDatabase()->getTables(true);

        try {
            if ($db instanceof MysqlDriver) {
                $this->out('Disable all indexes.');

                foreach ($tables as $table) {
                    $db->execute("ALTER TABLE `{$table}` DISABLE KEYS;");
                }
            }

            $this->console->container->call([$seeder, 'doExecute']);
        } catch (\PDOException $e) {
            if ($this->getOption('v')) {
                $this->out("\n\nError SQL: " . $this->console->database->getQuery());
            }

            throw $e;
        } finally {
            if ($db instanceof MysqlDriver) {
                $this->out('Re-enable all indexes.');

                foreach ($tables as $table) {
                    $db->execute("ALTER TABLE `{$table}` ENABLE KEYS;");
                }
            }
        }

        return true;
    }
}
