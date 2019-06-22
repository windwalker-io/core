<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Repository;

use Windwalker\Core\Ioc;
use Windwalker\Core\Repository\Traits\DatabaseRepositoryTrait;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\Record\Record;
use Windwalker\Structure\Structure;

/**
 * The DatabaseRepository class.
 *
 * @since  3.0
 */
class DatabaseRepository extends Repository implements DatabaseRepositoryInterface
{
    use DatabaseRepositoryTrait;

    /**
     * Instantiate the model.
     *
     * @param   Structure|array        $config The model config.
     * @param   AbstractDatabaseDriver $db     The database driver.
     *
     * @since   1.0
     */
    public function __construct($config = null, AbstractDatabaseDriver $db = null)
    {
        parent::__construct($config);

        $this->db = $db ?: Ioc::getDatabase();
    }

    /**
     * registerRecordEvents
     *
     * @param Record $record
     *
     * @return  Record
     *
     * @since  __DEPLOY_VERSION__
     */
    public function registerRecordEvents(Record $record): Record
    {
        return $record;
    }
}
