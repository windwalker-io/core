<?php

declare(strict_types=1);

namespace Windwalker\Core\Database;

use Closure;
use Windwalker\Query\Clause\Clause;
use Windwalker\Query\Clause\ClausePosition;
use Windwalker\Query\Data\CursorPaginate;
use Windwalker\Query\Query;
use Windwalker\Utilities\Wrapper\RawWrapper;

/**
 * Trait QueryProxyTrait
 *
 * @psalm-type QueryCallback = \Closure(Query): mixed
 * @psalm-type QueryCallable = callable(Query): mixed
 *
 * @method  $this          select(...$columns)
 * @method  $this          selectAs(mixed $column, ?string $alias = null, bool $isColumn = true)
 * @method  $this          selectRaw(mixed $column, ...$args)
 * @method  $this          whereRaw(Clause|string $string, ...$args)
 * @method  $this          havingRaw(mixed $string, mixed ...$args)
 * @method  $this          order(mixed $column, string|\SortDirection|null $dir = null)
 * @method  $this          orderRaw(string|Clause $order, mixed ...$args)
 * @method  $this          group(...$columns)
 * @method  $this          limit(?int $limit)
 * @method  $this          offset(?int $offset)
 * @method  $this          forUpdate(?string $do = null)
 * @method  $this          forShare(?string $do = null)
 * @method  $this          bindParam($key = null, &$value = null, $dataType = null, int $length = 0, $driverOptions = null)
 * @method  $this          bind($key = null, $value = null, $dataType = null)
 * @method  $this          resetBounded()
 * @method  $this          unbind(mixed $keys)
 * @method  array          getAllTables()
 * @method  $this          whereIn($column, array $values)
 * @method  $this          whereNotIn($column, array $values)
 * @method  $this          whereBetween($column, $start, $end)
 * @method  $this          whereNotBetween($column, $start, $end)
 * @method  $this          whereLike($column, string $search)
 * @method  $this          whereNotLike($column, string $search)
 * @method  $this          whereJsonContains(string $column, mixed $json, string $path = '$')
 * @method  $this          whereJsonNotContains(string $column, mixed $json, string $path = '$')
 * @method  $this          havingIn($column, array $values)
 * @method  $this          havingNotIn($column, array $values)
 * @method  $this          havingBetween($column, $start, $end)
 * @method  $this          havingNotBetween($column, $start, $end)
 * @method  $this          havingLike($column, string $search)
 * @method  $this          havingNotLike($column, string $search)
 * @method  $this          havingJsonContains(string $column, mixed $json, string $path = '$')
 * @method  $this          havingJsonNotContains(string $column, mixed $json, string $path = '$')
 * @method  $this          debug(bool $pre = false, bool $format = true, bool $asString = false)
 * @method  string|array   qn($text)
 * @method  string|array   q($text)
 * @method  string         getName()
 * @method  $this          orderByRandom(ClausePosition $pos = ClausePosition::APPEND)
 * @method  $this          orderByField(mixed $column, array $values, \SortDirection|string|null $dir = null, ClausePosition $pos = ClausePosition::APPEND)
 * @method  $this          groupRaw(Clause|string $group, mixed ...$args)
 * @method  $this          insert(string $table, bool $incrementField = false)
 * @method  $this          upsert(string $table, bool $incrementField = false)
 * @method  $this          onConflict(array|string ...$keys)
 * @method  $this          onConflictUpdateColumns(string ...$columns)
 * @method  $this          onConflictUpdateValues(array|string $column, mixed $value = null)
 * @method  $this          returning(?string $idField)
 * @method  $this          update(string $table, ?string $alias = null)
 * @method  $this          delete(string $table, ?string $alias = null)
 * @method  $this          columns(mixed ...$columns)
 * @method  $this          values(Query|array ...$valuesSet)
 * @method  $this          set(\Traversable|array|string $column, mixed $value = null)
 * @method  $this          setRaw(Clause|string $value, mixed ...$args)
 * @method  array|string   escape(mixed $value)
 * @method  mixed          quote(mixed $value)
 * @method  Clause|string  quoteName(\Stringable|string $name, int $flags = 0)
 * @method  $this          prefix(\Windwalker\Query\Clause\ClauseInterface|array|string $prefix, mixed ...$args)
 * @method  $this          suffix(\Windwalker\Query\Clause\ClauseInterface|array|string $suffix, mixed ...$args)
 * @method  $this          rowLock(string $for = 'UPDATE', ?string $do = null)
 * @method  string         format(string $format, mixed ...$args)
 * @method  object         mustGet(?string $class = null, array $args = [])
 * @method  mixed          mustGetResult()
 * @method  Query          createSubQuery()
 * @method  $this          setDefaultItemClass(?string $defaultItemClass)
 * @method  Clause         jsonSelector(string $expr, bool $instant = false)
 * @method  $this          whereJsonContainsAll(string $expr, mixed $value)
 * @method  $this          whereJsonNotContainsAll(string $expr, mixed $value)
 * @method  $this          havingJsonContainsAll(string $expr, mixed $value)
 * @method  $this          havingJsonNotContainsAll(string $expr, mixed $value)
 * @method  $this          selectJsonLength(string $expr, string $as = '')
 * @method  $this          whereJsonLength(array|string $expr, mixed ...$args)
 * @method  $this          whereJsonExists(string $expr)
 * @method  $this          whereJsonNotExists(string $expr)
 * @method  $this          havingJsonExists(string $expr)
 * @method  $this          havingJsonNotExists(string $expr)
 * @method  RawWrapper     jsonSet(string $expr, mixed $value)
 * @method  RawWrapper     jsonRemove(string $expr)
 * @method  $this          setJson(string $expr, mixed $value)
 * @method  $this          setJsonRemove(string $expr)
 * @method  \Generator     iterateBatched(CursorPaginate|int $paginate, ?string $class = null, array $args = [])
 * @method  \Generator     iterateChunks(CursorPaginate|int $paginate, ?string $class = null, array $args = [])
 * @method  \Generator     iterateWhile(int $length, ?string $class = null, array $args = [])
 * @method  \Generator     iterateBatchWhile(int $length, ?string $class = null, array $args = [])
 * @method  \Traversable   getIterator(?string $class = null, array $args = [], CursorPaginate|int|null $paginate = null)
 */
trait QueryProxyTrait
{
    abstract protected function getInnerQuery(): Query;

    /**
     * @param  string|array|Query|QueryCallable  $tables
     */
    public function from(mixed $tables, ?string $alias = null): static
    {
        $this->getInnerQuery()->from($tables, $alias);

        return $this;
    }

    /**
     * @param  string|Query|Clause|QueryCallable  $table
     */
    public function join(string $type, mixed $table, ?string $alias = null, mixed ...$on): static
    {
        $this->getInnerQuery()->join($type, $table, $alias, ...$on);

        return $this;
    }

    /**
     * @param  string|Query|Clause|QueryCallable  $table
     */
    public function joinLateral(string $type, mixed $table, ?string $alias = null, mixed ...$on): static
    {
        $this->getInnerQuery()->joinLateral($type, $table, $alias, ...$on);

        return $this;
    }

    /**
     * @param  string|Query|Clause|QueryCallable  $table
     */
    public function leftJoin(mixed $table, ?string $alias = null, mixed ...$on): static
    {
        $this->getInnerQuery()->leftJoin($table, $alias, ...$on);

        return $this;
    }

    /**
     * @param  string|Query|Clause|QueryCallable  $table
     */
    public function rightJoin(mixed $table, ?string $alias = null, mixed ...$on): static
    {
        $this->getInnerQuery()->rightJoin($table, $alias, ...$on);

        return $this;
    }

    /**
     * @param  string|Query|Clause|QueryCallable  $table
     */
    public function outerJoin(mixed $table, ?string $alias = null, mixed ...$on): static
    {
        $this->getInnerQuery()->outerJoin($table, $alias, ...$on);

        return $this;
    }

    /**
     * @param  string|Query|Clause|QueryCallable  $table
     */
    public function innerJoin(mixed $table, ?string $alias = null, mixed ...$on): static
    {
        $this->getInnerQuery()->innerJoin($table, $alias, ...$on);

        return $this;
    }

    /**
     * @param  string|Query|Clause|QueryCallable  $table
     */
    public function leftJoinLateral(mixed $table, ?string $alias = null, mixed ...$on): static
    {
        $this->getInnerQuery()->leftJoinLateral($table, $alias, ...$on);

        return $this;
    }

    /**
     * @param  string|Query|Clause|QueryCallable  $table
     */
    public function rightJoinLateral(mixed $table, ?string $alias = null, mixed ...$on): static
    {
        $this->getInnerQuery()->rightJoinLateral($table, $alias, ...$on);

        return $this;
    }
    /**
     * @param  string|Query|Clause|QueryCallable  $table
     */
    public function innerJoinLateral(mixed $table, ?string $alias = null, mixed ...$on): static
    {
        $this->getInnerQuery()->innerJoinLateral($table, $alias, ...$on);

        return $this;
    }

    /**
     * @param  string|array|QueryCallable|Clause  $column  Column name, array where list or callback
     */
    public function where(mixed $column, mixed ...$args): static
    {
        $this->getInnerQuery()->where($column, ...$args);

        return $this;
    }

    /**
     * @param  string|array|QueryCallable|Clause  $column  Column name, array where list or callback
     */
    public function having(mixed $column, mixed ...$args): static
    {
        $this->getInnerQuery()->having($column, ...$args);

        return $this;
    }

    /**
     * @param  array|QueryCallback  $wheres
     */
    public function andWhere(array|Closure $wheres): static
    {
        $this->getInnerQuery()->andWhere($wheres);

        return $this;
    }

    /**
     * @param  Query|QueryCallable  $query
     */
    public function with(
        string $name,
        mixed $query,
        ?array $columns = null,
        bool $recursive = false,
        ?bool $materialized = null
    ): static {
        $this->getInnerQuery()->with($name, $query, $columns, $recursive, $materialized);

        return $this;
    }

    /**
     * @param  Query|QueryCallable  $query
     */
    public function withRecursive(
        string $name,
        mixed $query,
        ?array $columns = null,
        ?bool $materialized = null
    ): static {
        $this->getInnerQuery()->withRecursive($name, $query, $columns, $materialized);

        return $this;
    }

    /**
     * @param  Query|QueryCallable|string|array  $query
     */
    public function union(mixed $query, string $type = ''): static
    {
        $this->getInnerQuery()->union($query, $type);

        return $this;
    }

    /**
     * @param  Query|QueryCallable|string|array  $query
     */
    public function unionDistinct(mixed $query): static
    {
        $this->getInnerQuery()->unionDistinct($query);

        return $this;
    }

    /**
     * @param  Query|QueryCallable|string|array  $query
     */
    public function unionAll(mixed $query): static
    {
        $this->getInnerQuery()->unionAll($query);

        return $this;
    }

    /**
     * @param  array|QueryCallable  $values
     *
     * @deprecated  Use upsert() in the future.
     */
    public function onDuplicateKeyUpdate(array|callable $values): static
    {
        $this->getInnerQuery()->onDuplicateKeyUpdate($values);

        return $this;
    }

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
