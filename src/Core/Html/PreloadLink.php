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
    /**
     * @codeCoverageIgnore
     */
    public function getType(): ?string
    {
        return $this->getAttributes()['type'] ?? null;
    }

    /**
     * @codeCoverageIgnore
     */
    public function withType(string $type): static
    {
        return $this->withAttribute('type', $type);
    }

    /**
     * @codeCoverageIgnore
     */
    public function withoutType(): static
    {
        return $this->withoutAttribute('type');
    }

    /**
     * @codeCoverageIgnore
     */
    public function getAs(): ?string
    {
        return $this->getAttributes()['as'] ?? null;
    }

    /**
     * @codeCoverageIgnore
     */
    public function withAs(string $as): static
    {
        return $this->withAttribute('as', $as);
    }

    /**
     * @codeCoverageIgnore
     */
    public function withoutAs(): static
    {
        return $this->withoutAttribute('as');
    }

    /**
     * @codeCoverageIgnore
     */
    public function getMedia(): ?string
    {
        return $this->getAttributes()['media'] ?? null;
    }

    /**
     * @codeCoverageIgnore
     */
    public function withMedia(string $media): static
    {
        return $this->withAttribute('media', $media);
    }

    /**
     * @codeCoverageIgnore
     */
    public function withoutMedia(): static
    {
        return $this->withoutAttribute('media');
    }
}
