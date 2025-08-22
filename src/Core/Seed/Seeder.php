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
class Seeder extends AbstractSeeder
{
    use InstanceMarcoableTrait;

    public protected(set) ?\Closure $importHandler = null;

    public protected(set) ?\Closure $clearHandler = null;

    /**
     * @param  callable  $import
     *
     * @return  static  Return self to support chaining.
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
     */
    public function clear(callable $clear): static
    {
        $this->clearHandler = $clear(...);

        return $this;
    }

    public function getImportHandler(): ?\Closure
    {
        return $this->importHandler;
    }

    public function getClearHandler(): ?\Closure
    {
        return $this->clearHandler;
    }
}
