<?php

declare(strict_types=1);

namespace Windwalker\Core\Seed;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use SplFileInfo;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Utilities\Classes\InstanceMarcoableTrait;
use Windwalker\Utilities\StrNormalize;

/**
 * The Seeder class.
 *
 * @deprecated  Use class SeedTask instead.
 */
class Seeder extends SeederTask
{
    use InstanceMarcoableTrait;

    public ?\Closure $import = null;

    public ?\Closure $clear = null;

    /**
     * @param  callable  $import
     *
     * @return  static  Return self to support chaining.
     */
    public function import(callable $import): static
    {
        $this->import = $import(...);

        return $this;
    }

    /**
     * @param  callable  $clear
     *
     * @return  static  Return self to support chaining.
     */
    public function clear(callable $clear): static
    {
        $this->clear = $clear(...);

        return $this;
    }

    public function getImportClosure(): ?\Closure
    {
        return $this->import;
    }

    public function getClearClosure(): ?\Closure
    {
        return $this->clear;
    }
}
