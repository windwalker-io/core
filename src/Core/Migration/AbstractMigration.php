<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration;

use SplFileInfo;
use Windwalker\Core\Seed\CountingOutputTrait;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Manager\TableManager;
use Windwalker\ORM\ORM;
use Windwalker\Utilities\Attributes\AttributesAccessor;
use Windwalker\Utilities\Classes\InstanceMarcoableTrait;

abstract class AbstractMigration
{
    use InstanceMarcoableTrait;
    use CountingOutputTrait;

    public const MigrationDirection UP = MigrationDirection::UP;

    public const MigrationDirection DOWN = MigrationDirection::DOWN;

    public protected(set) string $version;

    public protected(set) string $name;

    public ?string $hash = null;

    public string $fullName {
        get => $this->version . '_' . $this->name;
    }

    public protected(set) SplFileInfo $file;

    public protected(set) DatabaseAdapter $db;

    public ORM $orm {
        get => $this->db->orm();
    }

    public function init(
        SplFileInfo $file,
        DatabaseAdapter $db
    ): static {
        $name = $file->getBasename('.php');

        [$version, $name] = explode('_', $name, 2);

        $this->file = $file;
        $this->db = $db;
        $this->version = $version;
        $this->name = $name;

        return $this;
    }

    public function get(MigrationDirection $direction): ?\Closure
    {
        return $direction === MigrationDirection::UP ? $this->getUpHandler() : $this->getDownHandler();
    }

    protected function getUpHandler(): ?\Closure
    {
        if (!$found = $this->getReflectionMethod(MigrateUp::class)) {
            return null;
        }

        return $found[0]->getClosure($this);
    }

    protected function getDownHandler(): ?\Closure
    {
        if (!$found = $this->getReflectionMethod(MigrateDown::class)) {
            return null;
        }

        return $found[0]->getClosure($this);
    }

    /**
     * @return  array{ \ReflectionMethod, \ReflectionAttribute<object> }|null
     */
    protected function getReflectionMethod(string $attr): ?array
    {
        return AttributesAccessor::getFirstMemberWithAttribute(
            $this,
            $attr,
            \ReflectionAttribute::IS_INSTANCEOF,
            \ReflectionMethod::class
        );
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
        return $this->db->getTableManager($name, true);
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
