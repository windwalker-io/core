<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Edge\Component;

use Closure;
use RuntimeException;
use Windwalker\Core\Renderer\LayoutPathResolver;
use Windwalker\Edge\Component\DynamicComponent;

/**
 * The XComponent class.
 */
class XComponent extends DynamicComponent
{
    /**
     * XComponent constructor.
     */
    public function __construct(protected LayoutPathResolver $pathResolver)
    {
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return Closure|string
     */
    public function render(): Closure|string
    {
        try {
            $this->is = $this->pathResolver->resolveLayout($this->is);
        } catch (RuntimeException $e) {
            //
        }

        return parent::render();
    }
}
