<?php

declare(strict_types=1);

namespace Windwalker\Core\CliServer\Swoole;

use Swoole\Table;

/**
 * The TableRegistry class.
 */
class TableRegistry
{
    protected static array $tables = [];

    /**
     * @codeCoverageIgnore
     */
    public static function create(int $size, float $conflictProportion = 0.2): Table
    {
        return new Table($size, $conflictProportion);
    }

    public static function get(string $name, int $size, float $conflictProportion = 0.2): Table
    {
        return self::$tables[$name] ??= self::create($size, $conflictProportion);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function set(string $name, Table $table): Table
    {
        return self::$tables[$name] = $table;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function remove(string $name): void
    {
        unset(self::$tables[$name]);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function all(): array
    {
        return self::$tables;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function clear(): void
    {
        self::$tables = [];
    }
}
