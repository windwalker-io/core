<?php

/**
 * Part of unicorn project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

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
