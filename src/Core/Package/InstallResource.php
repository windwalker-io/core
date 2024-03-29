<?php

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

    public array $modules = [];

    public array $files = [];

    protected array $callbacks = [];

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
        $this->$name = array_merge(
            $this->$name,
            $data
        );

        return $this;
    }

    public function dump(): array
    {
        return Arr::only(
            get_object_vars($this),
            [
                'config',
                'migrations',
                'seeders',
                'languages',
                'routes',
                'views',
                'modules',
                'files',
            ]
        );
    }

    public function addCallback(callable $callback): static
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    /**
     * @return array
     */
    public function getCallbacks(): array
    {
        return $this->callbacks;
    }

    /**
     * @param  array  $callbacks
     *
     * @return  static  Return self to support chaining.
     */
    public function setCallbacks(array $callbacks): static
    {
        $this->callbacks = $callbacks;

        return $this;
    }
}
