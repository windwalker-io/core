<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer\Swoole;

use Swoole\Table;

/**
 * The TableRegistry class.
 */
class TableRegistry
{
    protected static array $tables = [];

    public static function create(int $size, float $conflictProportion = 0.2): Table
    {
        return new Table($size, $conflictProportion);
    }

    public static function get(string $name, int $size, float $conflictProportion = 0.2): Table
    {
        return self::$tables[$name] ??= self::create($size, $conflictProportion);
    }

    public static function set(string $name, Table $table): Table
    {
        return self::$tables[$name] = $table;
    }

    public static function remove(string $name): void
    {
        unset(self::$tables[$name]);
    }

    public static function all(): array
    {
        return self::$tables;
    }

    public static function clear(): void
    {
        self::$tables = [];
    }
}
