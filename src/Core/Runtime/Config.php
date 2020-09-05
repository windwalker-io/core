<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Runtime;

use Windwalker\Data\Collection;
use Windwalker\Utilities\Wrapper\ValueReference;

/**
 * The Config class.
 */
class Config extends Collection
{
    /**
     * getDeep
     *
     * @param  string  $path
     * @param  string  $delimiter
     *
     * @return  mixed
     *
     * @since  __DEPLOY_VERSION__
     */
    public function &getDeep(string $path, string $delimiter = '.')
    {
        $value = parent::getDeep($path, $delimiter);

        if ($value === null && $this->parent) {
            $value = $this->parent->getDeep($path, $delimiter);
        }

        while ($value instanceof ValueReference) {
            $value = $value($this, $value->getDelimiter() ?? $delimiter);
        }

        return $value;
    }

    /**
     * Get value from this object.
     *
     * @param  mixed  $key
     *
     * @return  mixed
     */
    public function &get($key)
    {
        $value = parent::get($key);

        if ($value === null && $this->parent) {
            $value = $this->parent->get($key);
        }

        while ($value instanceof ValueReference) {
            $value = $value($this);
        }

        return $value;
    }

    public function hasDeep(string $path, ?string $delimiter = '.'): bool
    {
        return $this->getDeep($path, $delimiter) !== null;
    }

    public function has($key): bool
    {
        return $this->get($key) !== null;
    }
}
