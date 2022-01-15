<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Asset;

use ArrayAccess;
use Fig\Link\Link;
use Windwalker\Utilities\Contract\AccessibleInterface;
use Windwalker\Utilities\Options\OptionAccessTrait;

/**
 * The AssetLink class.
 */
class AssetLink extends Link
{
    use OptionAccessTrait;

    /**
     * @inheritDoc
     */
    public function __construct(string $href = '', array $options = [])
    {
        parent::__construct('', $href);

        $this->prepareOptions([], $options);
    }

    public function withAttributes(array $attrs): static
    {
        $new = clone $this;

        foreach ($attrs as $name => $value) {
            $new = $new->withAttribute($name, $value);
        }

        return $new;
    }

    public function withOption(string $name, mixed $value): static
    {
        $new = clone $this;
        $new->options[$name] = $value;

        return $new;
    }

    public function withOptions(array|ArrayAccess|AccessibleInterface $options): static
    {
        $new = clone $this;
        $new->options = $options;

        return $new;
    }

    public function sri(string $hash): static
    {
        $this->options['sri'] = $hash;

        return $this;
    }

    public function option(string $name, mixed $value): static
    {
        $this->options[$name] = $value;

        return $this;
    }
}
