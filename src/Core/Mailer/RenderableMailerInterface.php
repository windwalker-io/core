<?php

declare(strict_types=1);

namespace Windwalker\Core\Mailer;

use Windwalker\Core\Asset\AssetService;

/**
 * Interface RenderableMailterInterface
 */
interface RenderableMailerInterface
{
    /**
     * renderBody
     *
     * @param  string  $path
     * @param  array   $data
     * @param  array   $options
     *
     * @return  string
     */
    public function renderBody(string $layout, array $data = [], array $options = []): string;

    public function createAssetService(): AssetService;
}
