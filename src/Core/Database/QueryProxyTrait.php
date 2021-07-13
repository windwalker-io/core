<?php

/**
 * Part of unicorn project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Database;

use Windwalker\Query\Clause\Clause;
use Windwalker\Query\Query;

/**
 * Trait QueryProxyTrait
 *
 * @method  $this  select(...$columns)
 * @method  $this  selectAs(mixed $column, ?string $alias = null, bool $isColumn = true)
 * @method  $this  selectRaw(mixed $column, ...$args)
 * @method  $this  from(mixed $tables, ?string $alias = null)
 * @method  $this  join(string $type, mixed $table, ?string $alias = null, ...$on)
 * @method  $this  where(mixed $column, mixed ...$args)
 * @method  $this  whereRaw(Clause|string $string, ...$args)
 * @method  $this  orWhere(array|\Closure $wheres)
 * @method  $this  having(mixed $column, mixed ...$args)
 * @method  $this  havingRaw(mixed $string, mixed ...$args)
 * @method  $this  orHaving(array|\Closure $wheres)
 * @method  $this  order(mixed $column, ?string $dir = null)
 * @method  $this  group(...$columns)
 * @method  $this  limit(?int $limit)
 * @method  $this  offset(?int $offset)
 * @method  $this  forUpdate(?string $do = null)
 * @method  $this  forShare(?string $do = null)
 * @method  $this  bindParam($key = null, &$value = null, $dataType = null, int $length = 0, $driverOptions = null)
 * @method  $this  bind($key = null, $value = null, $dataType = null)
 * @method  $this  resetBounded()
 * @method  $this  unbind(mixed $keys)
 * @method  array  getAllTables()
 * @method $this leftJoin($table, ?string $alias = null, ...$on)
 * @method $this rightJoin($table, ?string $alias = null, ...$on)
 * @method $this outerJoin($table, ?string $alias = null, ...$on)
 * @method $this innerJoin($table, ?string $alias = null, ...$on)
 * @method $this whereIn($column, array $values)
 * @method $this whereNotIn($column, array $values)
 * @method $this whereBetween($column, $start, $end)
 * @method $this whereNotBetween($column, $start, $end)
 * @method $this whereLike($column, string $search)
 * @method $this whereNotLike($column, string $search)
 * @method $this havingIn($column, array $values)
 * @method $this havingNotIn($column, array $values)
 * @method $this havingBetween($column, $start, $end)
 * @method $this havingNotBetween($column, $start, $end)
 * @method $this havingLike($column, string $search)
 * @method $this havingNotLike($column, string $search)
 * @method $this debug(bool $pre = false, bool $format = true, bool $asString = false)
 * @method string|array qn($text)
 * @method string|array q($text)
 */
trait QueryProxyTrait
{
    abstract protected function getInnerQuery(): Query;

    public function __call(string $name, array$args = []): mixed
    {
        $query = $this->getInnerQuery();

        $query->$name(...$args);

        return $this;
    }
}
