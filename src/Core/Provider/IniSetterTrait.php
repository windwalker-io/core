<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\DI\Container;

/**
 * IniSetterTrait
 *
 * @since  4.0
 */
trait IniSetterTrait
{
    public static function setINI(string $key, $value, Container $container): void
    {
        if (is_callable($value)) {
            $value = $container->call($value, [$container]);
        }

        ini_set($key, (string) $value);
    }

    protected static function setINIValues(array $values, Container $container): void
    {
        foreach ($values as $key => $value) {
            static::setINI($key, $value, $container);
        }
    }
}
