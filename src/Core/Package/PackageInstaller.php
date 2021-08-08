<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Package;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;

/**
 * The PackageInsteller class.
 */
class PackageInstaller
{
    public InstallResource $installResources;

    public array $tags = [
        //
    ];

    /**
     * @var static[]
     */
    public array $children = [];

    /**
     * PackageInstaller constructor.
     *
     * @param  string|null           $name
     * @param  ApplicationInterface  $app
     */
    public function __construct(public ?string $name, protected ApplicationInterface $app)
    {
        $this->installResources = new InstallResource();
    }

    public function getChild(string $name): static
    {
        return $this->children[$name] ??= new static($name, $this->app);
    }

    public function installConfig(string|array $path, string|array $tags = []): static
    {
        return $this->installResource(
            'config',
            Filesystem::globAll($path),
            $this->app->path('@etc/packages/'),
            $tags
        );
    }

    public function installMigrations(string $path, string|array $tags = []): static
    {
        return $this->installResource(
            'migrations',
            Filesystem::globAll($path),
            $this->app->path('@migrations'),
            $tags
        );
    }

    public function installLanguages(string $path, string|array $tags = []): static
    {
        return $this->installResource(
            'languages',
            Filesystem::globAll($path),
            $this->app->path('@languages'),
            $tags
        );
    }

    public function installRoutes(string $path, string|array $tags = []): static
    {
        return $this->installResource(
            'routes',
            Filesystem::globAll($path),
            $this->app->path('@routes/packages/'),
            $tags
        );
    }

    public function installViews(string $path, string|array $tags = []): static
    {
        return $this->installResource(
            'views',
            Filesystem::globAll($path),
            $this->app->path('@views/packages/'),
            $tags
        );
    }

    public function installSeeders(string $path, string|array $tags = []): static
    {
        return $this->installResource(
            'seeders',
            Filesystem::globAll($path),
            $this->app->path('@seeders'),
            $tags
        );
    }

    protected function installResource(string $name, iterable $files, string $dest, string|array $tags = []): static
    {
        $items = [];

        /** @var FileObject $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $items[$file->getRealPath()] = $dest . '/' . $file->getRelativePathname();
        }

        return $this->addResources($name, $items, $tags);
    }

    protected function addResources(string $name, array $items, string|array $tags): static
    {
        $this->installResources->add($name, $items);

        foreach ((array) $tags as $tag) {
            $tagResource = $this->tags[$tag] ??= new InstallResource($tag);

            $tagResource->add($name, $items);
        }

        return $this;
    }
}
