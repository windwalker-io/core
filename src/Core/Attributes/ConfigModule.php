<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Composer\InstalledVersions;
use Windwalker\Utilities\Iterator\PriorityQueue;

#[\Attribute(\Attribute::TARGET_FUNCTION)]
class ConfigModule
{
    public \Closure $callback;

    public string $file;

    public string $basename {
        get => basename(basename($this->file, '.php'), '.config');
    }

    public array $config {
        get => $this->config ??= ($this->callback)($this);
        set => $this->config = $value;
    }

    public function __construct(
        public ?string $name = null,
        public bool $enabled = true,
        public int $priority = PriorityQueue::ABOVE_NORMAL,
        public string|array|null $env = null,
        public ?string $belongsTo = null,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->enabled && $this->isEnvAllowed() && $this->isPackageAvailable();
    }

    public function isPackageAvailable(): bool
    {
        if ($this->belongsTo === null) {
            return true;
        }

        if (str_contains($this->belongsTo, '/')) {
            return InstalledVersions::isInstalled($this->belongsTo);
        }

        return class_exists($this->belongsTo);
    }

    public function isEnvAllowed(): bool
    {
        if ($this->env === null) {
            return true;
        }

        $currentEnv = env('APP_ENV') ?: 'prod';

        return in_array($currentEnv, (array) $this->env, true);
    }
}
