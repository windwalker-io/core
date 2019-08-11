<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Command\Queue;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Migration\Command\MigrationCommandTrait;

/**
 * The WorkerCommand class.
 *
 * @since  3.2
 */
class TableCommand extends CoreCommand
{
    use MigrationCommandTrait;

    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'table';

    /**
     * Property description.
     *
     * @var  string
     */
    protected $description = 'Create jobs migraiton file.';

    /**
     * Property usage.
     *
     * @var  string
     */
    protected $usage = '%s <cmd><class_name></cmd> <option>[option]</option>';

    /**
     * init
     *
     * @return  void
     */
    protected function init()
    {
    }

    /**
     * doExecute
     *
     * @return  bool
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    protected function doExecute()
    {
        $repository = $this->getRepository();

        $repository->copyMigration(
            $this->getArgument(0, 'QueueJobInit'),
            __DIR__ . '/../../../Resources/Templates/migration/queue_jobs.tpl'
        );

        return true;
    }
}
