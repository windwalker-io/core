<?php

declare(strict_types=1);

namespace Windwalker\Core\DI;

class TaggingFactory implements ServiceFactoryInterface
{
    use ServiceFactoryTrait;

    protected string $id;

    protected string $configPrefix = '';

    protected string $defaultConfigKey = '';

    protected string $fallback = '';

    public function getConfigPrefix(): string
    {
        return $this->configPrefix;
    }

    public function useConfig(string $path): TaggingFactory
    {
        $this->configPrefix = $path;

        $this->config = $this->config->getParent()?->proxy($this->configPrefix);

        return $this;
    }

    protected function getFactoryPath(string $name): string
    {
        return 'factories.' . $this->id . '.' . $name;
    }

    public function getDefaultName(): ?string
    {
        if (!$this->defaultConfigKey) {
            return $this->fallback;
        }

        return $this->config->getDeep($this->defaultConfigKey) ?? $this->fallback;
    }

    protected function getDefaultFactory(string $name, ...$args): mixed
    {
        return $this->config->getDeep($this->getFactoryPath($this->getDefaultName()));
    }

    public function getFallback(): string
    {
        return $this->fallback;
    }

    public function fallback(string $fallback): static
    {
        $this->fallback = $fallback;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getDefaultConfigKey(): string
    {
        return $this->defaultConfigKey;
    }

    public function defaultConfigKey(string $defaultConfigKey): static
    {
        $this->defaultConfigKey = $defaultConfigKey;

        return $this;
    }
}
