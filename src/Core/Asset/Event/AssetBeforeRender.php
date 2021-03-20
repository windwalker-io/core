<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Asset\Event;

use Windwalker\Core\Asset\AssetService;
use Windwalker\Event\AbstractEvent;

/**
 * The AssetBeforeRender class.
 */
class AssetBeforeRender extends AbstractEvent
{
    public const TYPE_CSS = 'css';
    public const TYPE_JS = 'js';

    protected AssetService $assetManager;

    protected bool $withInternal = false;

    protected array $html = [];

    protected string $type;

    /**0
     * @return AssetService
     */
    public function getAssetManager(): AssetService
    {
        return $this->assetManager;
    }

    /**
     * @param  AssetService  $assetManager
     *
     * @return  static  Return self to support chaining.
     */
    public function setAssetManager(AssetService $assetManager)
    {
        $this->assetManager = $assetManager;

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
}
