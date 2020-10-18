<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Service;

use Windwalker\Renderer\RendererInterface;

/**
 * The RendererService class.
 */
class RendererService
{
    /**
     * RendererService constructor.
     *
     * @param  RendererInterface  $renderer
     * @param  array              $globals
     */
    public function __construct(protected RendererInterface $renderer, protected array $globals = [])
    {
    }

    public function render(string $layout, array $data, array $options = []): string
    {
        $data = array_merge($this->globals, $data);

        return $this->renderer->render($layout, $data, $options);
    }

    public function addGlobal(string $name, mixed $value): static
    {
        $this->globals[$name] = $value;

        return $this;
    }

    public function getGlobal(string $name): mixed
    {
        return $this->globals[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getGlobals(): array
    {
        return $this->globals;
    }

    /**
     * @param  array  $globals
     *
     * @return  static  Return self to support chaining.
     */
    public function setGlobals(array $globals)
    {
        $this->globals = $globals;

        return $this;
    }
}
