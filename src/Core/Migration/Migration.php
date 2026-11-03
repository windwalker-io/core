<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration;

/**
 * The Migration class.
 *
 * @deprecated  Use class extends AbstractMigration instead.
 */
final class Migration extends AbstractMigration
{
    protected ?\Closure $upHandler = null;

    protected ?\Closure $downHandler = null;

    /**
     * @param  callable  $up
     *
     * @return  static  Return self to support chaining.
     *
     * @codeCoverageIgnore
     */
    public function up(callable $up): static
    {
        $this->upHandler = $up(...);

        return $this;
    }

    /**
     * @param  callable  $down
     *
     * @return  static  Return self to support chaining.
     *
     * @codeCoverageIgnore
     */
    public function down(callable $down): static
    {
        $this->downHandler = $down(...);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getUpHandler(): ?\Closure
    {
        return $this->upHandler;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getDownHandler(): ?\Closure
    {
        return $this->downHandler;
    }
}
