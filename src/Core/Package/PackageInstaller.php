<?php

declare(strict_types=1);

namespace Windwalker\Core\Package;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Utilities\StrNormalize;

/**
 * The PackageInstaller class.
 */
class PackageInstaller
{
    public InstallResource $installResources;

    /**
     * @var array<InstallResource>
     */
    public array $tags = [
        //
    ];

    /**
     * @var static[]
     */
    public array $children = [];

    public ?string $name {
        get => $this->package ? $this->package::getName() : null;
    }

    /**
     * PackageInstaller constructor.
     *
     * @param  AbstractPackage|null  $package
     * @param  ApplicationInterface  $app
     */
    public function __construct(public protected(set) ?AbstractPackage $package, protected ApplicationInterface $app)
    {
        $this->installResources = new InstallResource();
    }

    public function getChild(AbstractPackage $package): static
    {
        return $this->children[$package::getName()] ??= new static($package, $this->app);
    }

    public function installConfig(string|array $path, string|array $tags = [], ?callable $callback = null): static
    {
        return $this->installResource(
            'config',
            Filesystem::globAll($path),
            $this->app->path('@etc/packages/'),
            $tags,
            $callback
        );
    }

    public function installMigrations(string $path, string|array $tags = [], ?callable $callback = null): static
    {
        return $this->installResource(
            'migrations',
            Filesystem::globAll($path),
            $this->app->path('@migrations'),
            $tags,
            $callback
        );
    }

    public function installLanguages(string $path, string|array $tags = [], ?callable $callback = null): static
    {
        return $this->installResource(
            'languages',
            Filesystem::globAll($path),
            $this->app->path('@languages'),
            $tags,
            $callback
        );
    }

    public function installRoutes(string $path, string|array $tags = [], ?callable $callback = null): static
    {
        return $this->installResource(
            'routes',
            Filesystem::globAll($path),
            $this->app->path('@routes/packages/'),
            $tags,
            $callback
        );
    }

    public function installFiles(
        string $path,
        string $dest,
        string|array $tags = [],
        ?callable $callback = null
    ): static {
        return $this->installResource(
            'files',
            Filesystem::globAll($path),
            $this->app->path('@root/' . $dest),
            $tags,
            $callback
        );
    }

    public function installViews(string $path, string|array $tags = [], ?callable $callback = null): static
    {
        return $this->installResource(
            'views',
            Filesystem::globAll($path),
            $this->app->path('@views/'),
            $tags,
            $callback
        );
    }

    public function installModules(
        array $paths,
        array $replaces = [],
        string|array $tags = [],
        ?callable $callback = null
    ): static {
        foreach ($paths as $src => $dest) {
            $this->installResource(
                'modules',
                Filesystem::globAll($src),
                $this->app->path($dest),
                $tags,
                function ($src, $dest) use ($callback, $replaces) {
                    $content = (string) Filesystem::read($dest);

                    $content = strtr($content, $replaces);

                    Filesystem::write($dest, $content);

                    if ($callback) {
                        $callback($src, $dest);
                    }
                }
            );
        }

        return $this;
    }

    /**
     * Example: $installer->installMVCModules('Banner', ['Front', 'Admin'], true);
     *
     * @param  string  $entity
     * @param  array   $modules
     * @param  bool    $model
     *
     * @return  $this
     *
     * @throws \ReflectionException
     */
    public function installMVCModules(
        string $entity,
        array $modules = [
            'Front',
            'Admin',
        ],
        bool $model = true,
    ): static {
        if (str_contains($entity, '\\')) {
            $segments = explode('\\', $entity);
            $entity = (string) array_pop($segments);
        }

        $snake = StrNormalize::toSnakeCase($entity);
        $entity = StrNormalize::toPascalCase($entity);

        $ref = new \ReflectionClass($this->package);
        $ns = $ref->getNamespaceName();

        foreach ($modules as $module) {
            $snakeModule = StrNormalize::toSnakeCase($module);
            $module = StrNormalize::toPascalCase($module);

            $this->installModules(
                [
                    $this->package::path("src/Module/$module/$entity/**/*") => "@source/Module/$module/$entity",
                ],
                [$ns . "\\Module\\$module" => "App\\Module\\$module"],
                ['modules', $snake . '_' . $snakeModule],
            );
        }

        if ($model) {
            $this->installModules(
                [
                    $this->package::path("src/Entity/$entity.php") => '@source/Entity',
                    $this->package::path("src/Repository/{$entity}Repository.php") => '@source/Repository',
                ],
                [
                    $ns . '\\Entity' => 'App\\Entity',
                    $ns . '\\Repository' => 'App\\Repository',
                ],
                ['modules', $snake . '_model']
            );
        }

        return $this;
    }

    public function installSeeders(string $path, string|array $tags = [], ?callable $callback = null): static
    {
        return $this->installResource(
            'seeders',
            Filesystem::globAll($path),
            $this->app->path('@seeders'),
            $tags,
            $callback
        );
    }

    public function installCallback(
        string|array $tags = [],
        ?callable $callback = null
    ): static {
        return $this->addResources('callbacks', [], $tags, $callback);
    }

    protected function installResource(
        string $name,
        iterable $files,
        string|callable $dest,
        string|array $tags = [],
        ?callable $callback = null
    ): static {
        $items = [];

        /** @var FileObject $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $items[$file->getRealPath()] = $dest . '/' . $file->getRelativePathname();
        }

        return $this->addResources($name, $items, $tags, $callback);
    }

    protected function addResources(string $name, array $items, string|array $tags, ?callable $callback = null): static
    {
        $this->installResources->add($name, $items);

        foreach ((array) $tags as $tag) {
            $tagResource = $this->tags[$tag] ??= new InstallResource($tag);

            $tagResource->add($name, $items);

            if ($callback) {
                $tagResource->addCallback($callback);
            }
        }

        return $this;
    }

    /**
     * @return  array<callable>
     */
    public function getAllCallbacks(array $tags): array
    {
        $callbacks = $this->installResources->getCallbacks();

        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                continue;
            }

            foreach ($this->tags[$tag]->getCallbacks() ?? [] as $callback) {
                $callbacks[] = $callback;
            }
        }

        return array_unique($callbacks, SORT_REGULAR);
    }
}
