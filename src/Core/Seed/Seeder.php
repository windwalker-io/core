<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Seed;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Utilities\StrNormalize;

/**
 * The Seeder class.
 */
class Seeder
{
    use CountingOutputTrait;

    public string $name;

    public string $prettyName;

    /**
     * @var callable
     */
    public $import = null;

    /**
     * @var callable
     */
    public $clear = null;

    /**
     * Migration constructor.
     *
     * @param  \SplFileInfo     $file
     * @param  DatabaseAdapter  $db
     * @param  FakerService     $faker
     */
    public function __construct(
        public \SplFileInfo $file,
        public DatabaseAdapter $db,
        protected FakerService $faker
    ) {
        $this->name = $this->file->getBasename('.php');
        $this->prettyName = ucwords(StrNormalize::toSpaceSeparated($this->name));
    }

    /**
     * @param  callable  $import
     *
     * @return  static  Return self to support chaining.
     */
    public function import(callable $import): static
    {
        $this->import = $import;

        return $this;
    }

    /**
     * @param  callable  $clear
     *
     * @return  static  Return self to support chaining.
     */
    public function clear(callable $clear): static
    {
        $this->clear = $clear;

        return $this;
    }

    public function faker(string $locale = FakerFactory::DEFAULT_LOCALE): Generator
    {
        return $this->faker->create($locale);
    }

    public function truncate(string ...$tables): static
    {
        foreach ($tables as $table) {
            $this->db->getTable($table)->truncate();
        }

        return $this;
    }
}
