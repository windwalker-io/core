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

        while ($value instanceof ValueReference) {
            $value = $value($this->storage, null, $delimiter);
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
        $value = parent::getDeep($key);

        while ($value instanceof ValueReference) {
            $value = $value($this->storage);
        }

        return $value;
    }
}
