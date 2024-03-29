<?php

declare(strict_types=1);

namespace Windwalker\Core\Asset\Event;

use Windwalker\Core\Asset\AssetLink;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Event\AbstractEvent;

/**
 * The AssetBeforeRender class.
 */
class AssetBeforeRender extends AbstractEvent
{
    public const TYPE_CSS = 'css';

    public const TYPE_JS = 'js';

    protected AssetService $assetService;

    protected bool $withInternal = false;

    protected array $html = [];

    protected array $links = [];

    protected array $internalAttrs = [];

    protected string $type;

    /**0
     * @return AssetService
     */
    public function getAssetService(): AssetService
    {
        return $this->assetService;
    }

    /**
     * @param  AssetService  $assetService
     *
     * @return  static  Return self to support chaining.
     */
    public function setAssetService(AssetService $assetService)
    {
        $this->assetService = $assetService;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWithInternal(): bool
    {
        return $this->withInternal;
    }

    /**
     * @param  bool  $withInternal
     *
     * @return  static  Return self to support chaining.
     */
    public function setWithInternal(bool $withInternal)
    {
        $this->withInternal = $withInternal;

        return $this;
    }

    /**
     * @return array
     */
    public function getHtml(): array
    {
        return $this->html;
    }

    /**
     * @param  array  $html
     *
     * @return  static  Return self to support chaining.
     */
    public function setHtml(array $html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param  string  $type
     *
     * @return  static  Return self to support chaining.
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function &getInternalAttrs(): array
    {
        return $this->internalAttrs;
    }

    /**
     * @param  array  $internalAttrs
     *
     * @return  static  Return self to support chaining.
     */
    public function setInternalAttrs(array $internalAttrs): static
    {
        $this->internalAttrs = $internalAttrs;

        return $this;
    }

    /**
     * @return array<AssetLink>
     */
    public function &getLinks(): array
    {
        return $this->links;
    }

    /**
     * @param  array  $links
     *
     * @return  static  Return self to support chaining.
     */
    public function setLinks(array $links): static
    {
        $this->links = $links;

        return $this;
    }
}
