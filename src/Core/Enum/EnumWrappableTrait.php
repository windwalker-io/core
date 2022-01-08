<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2022 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Enum;

/**
 * The EnumTrait class.
 */
trait EnumWrappableTrait
{
    public static function wrap(mixed $value): static
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::from($value);
    }

    public static function tryWrap(mixed $value): static
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::tryFrom($value);
    }
}
