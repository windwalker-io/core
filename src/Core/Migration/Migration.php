<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration;

use SplFileInfo;
use Windwalker\Core\Seed\CountingOutputTrait;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Manager\TableManager;
use Windwalker\Utilities\Classes\InstanceMarcoableTrait;

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
     */
    public function down(callable $down): static
    {
        $this->downHandler = $down(...);

        return $this;
    }

    protected function getUpHandler(): ?\Closure
    {
        return $this->upHandler;
    }

    protected function getDownHandler(): ?\Closure
    {
        return $this->downHandler;
    }
}
