<?php

declare(strict_types=1);

namespace Windwalker\Core\Seed;

use Windwalker\Utilities\Classes\InstanceMarcoableTrait;

/**
 * The Seeder class.
 *
 * @deprecated  Use class SeedTask instead.
 */
class Seeder extends AbstractSeeder
{
    use InstanceMarcoableTrait;

    public protected(set) ?\Closure $importHandler = null;

    public protected(set) ?\Closure $clearHandler = null;

    /**
     * @param  callable  $import
     *
     * @return  static  Return self to support chaining.
     *
     * @codeCoverageIgnore
     */
    public function import(callable $import): static
    {
        $this->importHandler = $import(...);

        return $this;
    }

    /**
     * @param  callable  $clear
     *
     * @return  static  Return self to support chaining.
     *
     * @codeCoverageIgnore
     */
    public function clear(callable $clear): static
    {
        $this->clearHandler = $clear(...);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getImportHandler(): ?\Closure
    {
        return $this->importHandler;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getClearHandler(): ?\Closure
    {
        return $this->clearHandler;
    }
}
