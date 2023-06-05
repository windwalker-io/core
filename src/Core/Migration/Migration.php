<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Migration;

use SplFileInfo;
use Windwalker\Core\Seed\CountingOutputTrait;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Manager\TableManager;
use Windwalker\Utilities\Classes\InstanceMarcoableTrait;

/**
 * The Migration class.
 */
class Migration
{
    use InstanceMarcoableTrait;
    use CountingOutputTrait;

    public const UP = 'up';

    public const DOWN = 'down';

    public string $version;

    public string $name;

    public string $fullName;

    /**
     * @var callable
     */
    protected $up = null;

    /**
     * @var callable
     */
    protected $down = null;

    /**
     * Migration constructor.
     *
     * @param  SplFileInfo     $file
     * @param  DatabaseAdapter  $db
     */
    public function __construct(
        public SplFileInfo $file,
        public DatabaseAdapter $db
    ) {
        $name = $this->file->getBasename('.php');

        [$id, $name] = explode('_', $name, 2);

        $this->version = $id;
        $this->name = $name;
        $this->fullName = $id . '_' . $name;
    }

    public function get(string $direction): ?callable
    {
        return $direction === static::UP ? $this->up : $this->down;
    }

    /**
     * @param  callable  $up
     *
     * @return  static  Return self to support chaining.
     */
    public function up(callable $up): static
    {
        $this->up = $up;

        return $this;
    }

    /**
     * @param  callable  $down
     *
     * @return  static  Return self to support chaining.
     */
    public function down(callable $down): static
    {
        $this->down = $down;

        return $this;
    }

    /**
     * Get DB table object.
     *
     * @param  string  $name
     *
     * @return  TableManager
     */
    public function getTable(string $name): TableManager
    {
        return $this->db->getTable($name, true);
    }

    /**
     * createTable
     *
     * @param  string    $name
     * @param  callable  $callback
     * @param  array     $options
     *
     * @return TableManager
     */
    public function createTable(string $name, callable $callback, array $options = []): TableManager
    {
        return $this->getTable($name)->create($callback, true, $options);
    }

    /**
     * updateTable
     *
     * @param  string    $name
     * @param  callable  $callback
     *
     * @return  TableManager
     */
    public function updateTable(string $name, callable $callback): TableManager
    {
        return $this->getTable($name)->update($callback);
    }

    /**
     * saveTable
     *
     * @param  string    $name
     * @param  callable  $callback
     * @param  array     $options
     *
     * @return TableManager
     */
    public function saveTable(string $name, callable $callback, $options = []): TableManager
    {
        return $this->getTable($name)->save($callback, true, $options);
    }

    /**
     * Drop a table.
     *
     * @param  string|array  $names
     *
     * @return  static
     */
    public function dropTables(string ...$names): static
    {
        foreach ($names as $name) {
            $this->getTable($name)->drop();
        }

        return $this;
    }

    public function dropTableColumns(string $table, ...$columns): static
    {
        if ($columns !== []) {
            $tm = $this->getTable($table);

            foreach ($columns as $column) {
                $tm->dropColumn($column);
            }
        }

        return $this;
    }
}
