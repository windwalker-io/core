<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Package;

use Windwalker\Utilities\Arr;

/**
 * The InstallResource class.
 */
class InstallResource
{
    public ?string $tag = null;

    public array $config = [];

    public array $migrations = [];

    public array $seeders = [];

    public array $languages = [];

    public array $routes = [];

    public array $views = [];

    /**
     * InstallResource constructor.
     *
     * @param  string|null  $tag
     */
    public function __construct(?string $tag = null)
    {
        $this->tag = $tag;
    }

    public function add(string $name, array $data): static
    {
        $this->$name = array_merge($this->$name, $data);
        return $this;
    }

    public function dump(): array
    {
        return Arr::only(
            get_object_vars($this),
            [
                'config',
                'migration',
                'seeders',
                'languages',
                'routes',
                'views'
            ]
        );
    }
}
