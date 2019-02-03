<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Migration;

use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Core\Database\Traits\DateFormatTrait;
use Windwalker\Core\Seeder\CountingOutputTrait;
use Windwalker\Core\Seeder\FakerService;
use Windwalker\Database\Command\AbstractTable;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\DI\Annotation\Inject;
use Windwalker\Utilities\Arr;

/**
 * The AbstractMigration class.
 *
 * @since  2.0
 */
abstract class AbstractMigration
{
    use DateFormatTrait;
    use CountingOutputTrait;

    public const UP = 'up';

    public const DOWN = 'down';

    public const CHARSET_UTF8 = 'utf8';

    public const CHARSET_UTF8MB4 = 'utf8mb4';

    /**
     * Property db.
     *
     * @var  AbstractDatabaseDriver
     */
    protected $db;

    /**
     * Property command.
     *
     * @var  AbstractCommand
     */
    protected $command;

    /**
     * Property version.
     *
     * @var string
     */
    protected $version;

    /**
     * Property defaultCharset.
     *
     * @var  string
     */
    public static $defaultCharset = self::CHARSET_UTF8MB4;

    /**
     * Property faker.
     *
     * @Inject()
     *
     * @var FakerService
     */
    protected $faker;

    /**
     * Class init.
     *
     * @param AbstractCommand        $command
     * @param AbstractDatabaseDriver $db
     */
    public function __construct(AbstractCommand $command, AbstractDatabaseDriver $db)
    {
        $this->command = $command;

        $this->db = $db;
    }

    /**
     * up
     *
     * @return  void
     */
    abstract public function up();

    /**
     * down
     *
     * @return  void
     */
    abstract public function down();

    /**
     * Get DB table object.
     *
     * @param string $name
     *
     * @return  AbstractTable
     */
    public function getTable($name)
    {
        return $this->db->getTable($name, true);
    }

    /**
     * createTable
     *
     * @param string   $name
     * @param callable $callback
     * @param bool     $ifNotExists
     * @param array    $options
     *
     * @return AbstractTable
     */
    public function createTable($name, callable $callback, $ifNotExists = true, $options = [])
    {
        $options = $this->prepareBCOptions($options);

        return $this->getTable($name)->create($callback, $ifNotExists, $options);
    }

    /**
     * updateTable
     *
     * @param string   $name
     * @param callable $callback
     *
     * @return  AbstractTable
     */
    public function updateTable($name, callable $callback)
    {
        return $this->getTable($name)->update($callback);
    }

    /**
     * saveTable
     *
     * @param string   $name
     * @param callable $callback
     * @param bool     $ifNotExists
     * @param array    $options
     *
     * @return AbstractTable
     */
    public function saveTable($name, callable $callback, $ifNotExists = true, $options = [])
    {
        $options = $this->prepareBCOptions($options);

        return $this->getTable($name)->save($callback, $ifNotExists, $options);
    }

    /**
     * Drop a table.
     *
     * @param   string $name
     *
     * @return  static
     */
    public function drop($name)
    {
        $this->getTable($name)->drop(true);

        return $this;
    }

    /**
     * Method to get property Db
     *
     * @return  AbstractDatabaseDriver
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Method to set property db
     *
     * @param   AbstractDatabaseDriver $db
     *
     * @return  static  Return self to support chaining.
     */
    public function setDb($db)
    {
        $this->db = $db;

        return $this;
    }

    /**
     * Method to get property Command
     *
     * @return  AbstractCommand
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Method to set property command
     *
     * @param   AbstractCommand $command
     *
     * @return  static  Return self to support chaining.
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Method to get property Version
     *
     * @return  string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Method to set property version
     *
     * @param   string $version
     *
     * @return  static  Return self to support chaining.
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * prepareBCOptions
     *
     * @param array $options
     *
     * @return  array
     *
     * @since  3.4.0.1
     */
    protected function prepareBCOptions(array $options)
    {
        $options = (array) Arr::def($options, 'charset', static::$defaultCharset);
        $options = (array) Arr::def(
            $options,
            'collate',
            static::$defaultCharset === static::CHARSET_UTF8 ? 'utf8_unicode_ci' : 'utf8mb4_unicode_ci'
        );

        return $options;
    }
}
