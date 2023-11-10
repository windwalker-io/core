<?php

declare(strict_types=1);

namespace Windwalker\Core\Edge\Component;

use Closure;
use RuntimeException;
use Windwalker\Core\Renderer\LayoutPathResolver;
use Windwalker\Edge\Component\DynamicComponent;
use Windwalker\Utilities\Cache\RuntimeCacheTrait;

/**
 * The XComponent class.
 */
class XComponent extends DynamicComponent
{
    use RuntimeCacheTrait;

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
            $this->is = $this->resolveLayout($this->is);
        } catch (RuntimeException $e) {
            //
        }

        return parent::render();
    }

    protected function resolveLayout(string $is): string
    {
        return static::$cacheStorage['layout:' . $is] ??= $this->pathResolver->resolveLayout($this->is);
    }
}
