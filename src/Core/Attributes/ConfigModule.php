<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

#[\Attribute(\Attribute::TARGET_FUNCTION)]
class ConfigModule
{
    public \Closure $callback;

    public array $config {
        get => $this->config ??= ($this->callback)();
        set => $this->config = $value;
    }

    public function __construct(public ?string $path = null, public int $ordering = 100)
    {
    }
}
