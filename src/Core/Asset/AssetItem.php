<?php

declare(strict_types=1);

namespace Windwalker\Core\Asset;

use Windwalker\Utilities\Options\OptionAccessTrait;

class AssetItem implements \Stringable
{
    use OptionAccessTrait;

    public function __construct(protected string $content = '', array $options = [])
    {
        $this->options = $options;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function isFooter(): bool
    {
        return (bool) ($this->options['footer'] ?? false);
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return (string) $this->content;
    }
}
