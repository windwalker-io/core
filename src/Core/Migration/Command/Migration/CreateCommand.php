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
class CreateCommand extends CoreCommand
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
    protected $name = 'create';

    /**
     * The command description.
     *
     * @var  string
     */
    protected $description = 'Create a migration version.';

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
    }

    /**
     * Execute this command.
     *
     * @return int|void
     */
    protected function doExecute()
    {
        $repository = $this->getRepository();

        $name = $this->getArgument(0);

        if (!$name) {
            throw new \InvalidArgumentException('Missing first argument "name"');
        }

        // Get template
        $repository->copyMigration(
            $name,
            __DIR__ . '/../../../Resources/Templates/migration/migration.tpl'
        );

        return true;
    }
}
