<?php

declare(strict_types=1);

namespace Windwalker\Core\Package;

use JsonException;
use Windwalker\Core\Application\ApplicationInterface;

/**
 * The PackageRegistry class.
 */
class PackageRegistry
{
    /**
     * @var AbstractPackage[]
     */
    protected array $packages = [];

    /**
     * @var AbstractPackage[][]
     */
    protected array $composerPackagesMap = [];

    public bool $discovered = false;

    protected PackageInstaller $installer;

    protected PackageMigrator $migrator;

    public protected(set) array $rootComposerJson = [];

    public protected(set) array $installedJson = [];

    /**
     * PackageRegistry constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected ApplicationInterface $app)
    {
    }

    public function getInstaller(): PackageInstaller
    {
        return $this->installer ??= new PackageInstaller(null, $this->app);
    }

    public function getMigrator(): PackageMigrator
    {
        return $this->migrator ??= new PackageMigrator(null, $this->app);
    }

    public function discover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->rootComposerJson = $mainComposer = json_decode(
            file_get_contents($this->app->path('@root/composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // todo: Add ignore config

        $this->installedJson = $installed = json_decode(
            file_get_contents($this->app->path('@root/vendor/composer/installed.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $jsons = $installed['packages'] ?? [];
        array_unshift($jsons, $mainComposer);

        foreach ($jsons as $manifest) {
            $options = $manifest['extra']['windwalker'] ?? null;

            if ($options === null) {
                continue;
            }

            $name = $manifest['name'] ?? null;

            foreach ($options['packages'] ?? [] as $packageClass) {
                if (!is_subclass_of($packageClass, AbstractPackage::class)) {
                    continue;
                }

                $this->packages[$packageClass] = $package = $this->app->make($packageClass);

                if ($name) {
                    $this->composerPackagesMap[$name] ??= [];
                    $this->composerPackagesMap[$name][] = $package;
                }
            }
        }

        $this->discovered = true;
    }

    public function prepareInstall(): PackageInstaller
    {
        $this->discover();

        $installer = $this->getInstaller();

        foreach ($this->packages as $package) {
            $package->install($installer->getChild($package));
        }

        return $installer;
    }

    public function prepareMigrate(): PackageMigrator
    {
        $this->discover();

        $migrator = $this->getMigrator();

        foreach ($this->packages as $package) {
            $package->migrate($migrator->getChild($package::getName()));
        }

        return $migrator;
    }

    public function addPackage(AbstractPackage $package): static
    {
        $this->packages[$package::class] = $package;

        return $this;
    }

    /**
     * @return AbstractPackage[]
     * @throws JsonException
     */
    public function getPackages(): array
    {
        $this->discover();

        return $this->packages;
    }

    /**
     * @param  AbstractPackage[]  $packages
     *
     * @return  static  Return self to support chaining.
     */
    public function setPackages(array $packages): static
    {
        $this->packages = $packages;

        return $this;
    }

    public function getPackage(string $name): ?AbstractPackage
    {
        return array_find($this->getPackages(), fn($package) => $package::getName() === $name);
    }

    /**
     * @return  AbstractPackage[][]
     *
     * @throws JsonException
     */
    public function getComposerPackagesMap(): array
    {
        $this->discover();

        return $this->composerPackagesMap;
    }
}
