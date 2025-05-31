<?php

declare(strict_types=1);

namespace Windwalker\Core\Asset\Event;

use Windwalker\Core\Asset\AssetLink;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The AssetBeforeRender class.
 */
class AssetBeforeRender extends BaseEvent
{
    use AccessorBCTrait;

    public const string TYPE_CSS = 'css';

    public const string TYPE_JS = 'js';

    public function __construct(
        public string $type,
        public AssetService $assetService,
        public bool $withInternal = false,
        public array $html = [],
        public array $links = [],
        public array $internalAttrs = [],
    ) {
    }

    /**
     * @return array
     *
     * @deprecated  Use property instead.
     */
    public function &getInternalAttrs(): array
    {
        return $this->internalAttrs;
    }

    /**
     * @return array<AssetLink>
     *
     * @deprecated  Use property instead.
     */
    public function &getLinks(): array
    {
        return $this->links;
    }
}
