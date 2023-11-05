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
    public function setINI(string $key, $value, Container $container): void
    {
        if (is_callable($value)) {
            $container->call($value, [$container]);
        } else {
            ini_set($key, (string) $value);
        }
    }

    protected function setINIValues(array $values, Container $container): void
    {
        foreach ($values as $key => $value) {
            $this->setINI($key, $value, $container);
        }
    }
}
