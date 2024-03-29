<?php

declare(strict_types=1);

namespace Windwalker\Core\Html;

use Windwalker\Core\Link\Link;

/**
 * The PreloadLink class.
 */
#[\AllowDynamicProperties]
class PreloadLink extends Link
{
    public function getType(): ?string
    {
        return $this->getAttributes()['type'] ?? null;
    }

    public function withType(string $type): static
    {
        return $this->withAttribute('type', $type);
    }

    public function withoutType(): static
    {
        return $this->withoutAttribute('type');
    }

    public function getAs(): ?string
    {
        return $this->getAttributes()['as'] ?? null;
    }

    public function withAs(string $as): static
    {
        return $this->withAttribute('as', $as);
    }

    public function withoutAs(): static
    {
        return $this->withoutAttribute('as');
    }

    public function getMedia(): ?string
    {
        return $this->getAttributes()['media'] ?? null;
    }

    public function withMedia(string $media): static
    {
        return $this->withAttribute('media', $media);
    }

    public function withoutMedia(): static
    {
        return $this->withoutAttribute('media');
    }
}
