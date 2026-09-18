<?php

declare(strict_types=1);

namespace Windwalker\Core\Database;

use Closure;
use Windwalker\Query\Clause\Clause;
use Windwalker\Query\Query;

/**
 * Trait QueryProxyTrait
 *
 * @psalm-type QueryCallback = \Closure(Query): mixed
 * @psalm-type QueryCallable = callable(Query): mixed
 *
 * @method  $this  select(...$columns)
 * @method  $this  selectAs(mixed $column, ?string $alias = null, bool $isColumn = true)
 * @method  $this  selectRaw(mixed $column, ...$args)
 * @method  $this  from(mixed $tables, ?string $alias = null)
 * @method  $this  join(string $type, mixed $table, ?string $alias = null, ...$on)
 * @method  $this  where(mixed $column, mixed ...$args)
 * @method  $this  whereRaw(Clause|string $string, ...$args)
 * @method  $this  having(mixed $column, mixed ...$args)
 * @method  $this  havingRaw(mixed $string, mixed ...$args)
 * @method  $this  order(mixed $column, ?string $dir = null)
 * @method  $this  orderRaw(string|Clause $order, mixed ...$args)
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
 * @method $this leftJoinLateral($table, ?string $alias = null, ...$on)
 * @method $this innerJoinLateral($table, ?string $alias = null, ...$on)
 * @method $this whereIn($column, array $values)
 * @method $this whereNotIn($column, array $values)
 * @method $this whereBetween($column, $start, $end)
 * @method $this whereNotBetween($column, $start, $end)
 * @method $this whereLike($column, string $search)
 * @method $this whereNotLike($column, string $search)
 * @method $this whereJsonContains(string $column, mixed $json, string $path = '$')
 * @method $this whereJsonNotContains(string $column, mixed $json, string $path = '$')
 * @method $this havingIn($column, array $values)
 * @method $this havingNotIn($column, array $values)
 * @method $this havingBetween($column, $start, $end)
 * @method $this havingNotBetween($column, $start, $end)
 * @method $this havingLike($column, string $search)
 * @method $this havingNotLike($column, string $search)
 * @method $this havingJsonContains(string $column, mixed $json, string $path = '$')
 * @method $this havingJsonNotContains(string $column, mixed $json, string $path = '$')
 * @method $this debug(bool $pre = false, bool $format = true, bool $asString = false)
 * @method string|array qn($text)
 * @method string|array q($text)
 */
trait QueryProxyTrait
{
    abstract protected function getInnerQuery(): Query;

    /**
     * @param  Query|QueryCallable  $conditions
     */
    public function whereExists(Query|callable $conditions): static
    {
        $this->getInnerQuery()->whereExists($conditions);

        return $this;
    }

    /**
     * @param  Query|QueryCallable  $conditions
     */
    public function whereNotExists(Query|callable $conditions): static
    {
        $this->getInnerQuery()->whereNotExists($conditions);

        return $this;
    }

    /**
     * @param  array|QueryCallback  $wheres
     */
    public function orWhere(array|Closure $wheres): static
    {
        $this->getInnerQuery()->orWhere($wheres);

        return $this;
    }

    /**
     * @param  Query|QueryCallable  $conditions
     */
    public function havingExists(Query|callable $conditions): static
    {
        $this->getInnerQuery()->havingExists($conditions);

        return $this;
    }

    /**
     * @param  Query|QueryCallable  $conditions
     */
    public function havingNotExists(Query|callable $conditions): static
    {
        $this->getInnerQuery()->havingNotExists($conditions);

        return $this;
    }

    /**
     * @param  array|QueryCallback  $wheres
     */
    public function orHaving(array|Closure $wheres): static
    {
        $this->getInnerQuery()->orHaving($wheres);

        return $this;
    }

    public function __call(string $name, array $args = []): mixed
    {
        $query = $this->getInnerQuery();

        $result = $query->$name(...$args);

        if ($result === $query) {
            return $this;
        }

        return $result;
    }
}
