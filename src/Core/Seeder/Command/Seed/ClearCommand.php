<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Seeder\Command\Seed;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Ioc;
use Windwalker\Core\Migration\Repository\BackupRepository;

/**
 * Class Seed
 */
class ClearCommand extends CoreCommand
{
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
    protected $name = 'clear';

    /**
     * The command description.
     *
     * @var  string
     */
    protected $description = 'Clear seeders.';

    /**
     * The usage to tell user how to use this command.
     *
     * @var string
     */
    protected $usage = 'clear <cmd><command></cmd> <option>[option]</option>';

    /**
     * Initialise command information.
     *
     * @return void
     */
    public function init()
    {
        parent::init();
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
            throw new \RuntimeException('<error>STOP!</error> <comment>you must run seeder in dev mode</comment>.');
        }

        // backup
        if (!$this->getOption('no-backup')) {
            BackupRepository::getInstance()->setCommand($this)->backup();
        }

        $class = $this->console->get('seed.class');

        /** @var \Windwalker\Core\Seeder\AbstractSeeder $seeder */
        $seeder = $this->console->container->newInstance($class, ['command' => $this]);

        try {
            $this->console->container->call([$seeder, 'doClear']);
        } catch (\PDOException $e) {
            if ($this->getOption('v')) {
                $e = new \PDOException(
                    $e->getMessage() . "\n\nSQL: " . $this->console->database->getQuery(),
                    $e->getCode(),
                    $e
                );
            }

            throw $e;
        }

        $this->out('All data has been cleared.');

        return true;
    }
}
