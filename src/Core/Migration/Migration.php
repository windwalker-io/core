<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Migration;

use Windwalker\Database\DatabaseAdapter;
use Windwalker\Filesystem\FileObject;

/**
 * The Migration class.
 */
class Migration
{
    public const UP = 'up';

    public const DOWN = 'down';

    public string $version;
    public string $name;
    public string $fullName;

    /**
     * @var callable
     */
    protected $up = null;

    /**
     * @var callable
     */
    protected $down = null;

    /**
     * Migration constructor.
     *
     * @param  \SplFileInfo     $file
     * @param  DatabaseAdapter  $db
     */
    public function __construct(
        public \SplFileInfo $file,
        public DatabaseAdapter $db
    ) {
        $name = $this->file->getBasename('.php');

        [$id, $name] = explode('_', $name, 2);

        $this->version = $id;
        $this->name    = $name;
        $this->fullName = $id . '_' . $name;
    }

    public function get(string $direction): ?callable
    {
        return $direction === static::UP ? $this->up : $this->down;
    }

    /**
     * @param  callable  $up
     *
     * @return  static  Return self to support chaining.
     */
    public function up(callable $up): static
    {
        $this->up = $up;

        return $this;
    }

    /**
     * @param  callable  $down
     *
     * @return  static  Return self to support chaining.
     */
    public function down(callable $down): static
    {
        $this->down = $down;

        return $this;
    }
}
