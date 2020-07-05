<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Migration\Command\Migration;

use Windwalker\Core\Console\CoreCommand;

/**
 * The EnableIndexesCommand class.
 *
 * @since  __DEPLOY_VERSION__
 */
class EnableIndexesCommand extends CoreCommand
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'enable-indexes';

    /**
     * Property description.
     *
     * @var  string
     */
    protected $description = 'Enable all indexes';

    /**
     * The usage to tell user how to use this command.
     *
     * @var string
     */
    protected $usage = '%s <cmd><command></cmd> <option>[option]</option>';

    /**
     * The manual about this command.
     *
     * @var  string
     */
    protected $help;

    /**
     * Initialise command.
     *
     * @return void
     */
    protected function init()
    {
        parent::init();
    }

    /**
     * Execute this command.
     *
     * @return int|bool
     */
    protected function doExecute()
    {
        $db = $this->console->database;

        $tables = $db->getDatabase()->getTables();

        foreach ($tables as $table) {
            $this->out("Enable keys for table: {$table}");

            $db->execute("ALTER TABLE `{$table}` ENABLE KEYS;");
        }

        return true;
    }
}
