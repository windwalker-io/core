<?php

declare(strict_types=1);

namespace Windwalker\Core\Asset;

use Psr\Http\Message\UriInterface;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Runtime\Config;
use Windwalker\Uri\Uri;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

class ViteResolver
{
    use InstanceCacheTrait;

    public string $alias = '@vite';

    public string $base {
        get => $this->base ??= $this->getBase();
    }

    public function __construct(protected Config $config, protected PathResolver $pathResolver)
    {
        $this->config = $this->config->proxy('asset.vite');
    }

    public function isActive(): bool
    {
        return $this->getManifest() !== null || $this->serverIsRunning();
    }

    public function isDevServerUri(string $uri): bool
    {
        $host = $this->getServerHost();

        if (!$host || !$this->serverIsRunning()) {
            return false;
        }

        $serverHostString = $host->toString(Uri::FULL_HOST);

        return str_starts_with($uri, $serverHostString);
    }

    public function resolve(string $uri): string
    {
        $path = str_replace($this->alias, $this->base, $uri);

        if ($this->serverIsRunning()) {
            return $this->prependHost($path);
        }

        if (null === $manifest = $this->getManifest()) {
            return $uri;
        }

        $uri = $manifest[$path]['file'] ?? $uri;

        return $uri;
    }

    public function prependHost(string $uri): string
    {
        return rtrim((string) $this->getServerHost(), '/') . '/' . ltrim($uri, '/');
    }

    public function shouldResolve(string $uri): bool
    {
        return str_starts_with($uri, $this->alias) && $this->getManifest() !== null;
    }

    protected function getBase(): string
    {
        return $this->config->getDeep('alias') ?? 'resources';
    }

    public function getManifest(bool $refresh = false): ?array
    {
        return $this->once(
            'manifest',
            fn () => $this->loadManifest(),
            $refresh
        );
    }

    protected function loadManifest(): ?array
    {
        $manifestFile = $this->pathResolver->resolve(
            $this->config->get('manifest') ?? '@public/assets/manifest.json'
        );

        if (!is_file($manifestFile)) {
            return null;
        }

        $json = file_get_contents($manifestFile);

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function serverIsRunning(): bool
    {
        return $this->getServerHost() !== null;
    }

    public function getServerHost(): ?Uri
    {
        return $this->once(
            'server.host',
            fn () => $this->loadServerFile()
        );
    }

    protected function loadServerFile(): ?Uri
    {
        $serverFile = $this->pathResolver->resolve(
            $this->config->getDeep('vite.server_file') ?? 'tmp/vite-server'
        );

        if (!is_file($serverFile)) {
            return null;
        }

        return new Uri(trim(file_get_contents($serverFile)))->withHost('localhost');
    }
}
