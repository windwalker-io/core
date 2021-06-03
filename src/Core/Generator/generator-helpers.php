<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

use Windwalker\Utilities\StrInflector;
use Windwalker\Utilities\StrNormalise;

if (!function_exists('kebab')) {
    function kebab(string $string): string
    {
        return StrNormalise::toKebabCase($string);
    }
}

if (!function_exists('camel')) {
    function camel(string $string): string
    {
        return StrNormalise::toCamelCase($string);
    }
}

if (!function_exists('pascal')) {
    function pascal(string $string): string
    {
        return StrNormalise::toPascalCase($string);
    }
}

if (!function_exists('snake')) {
    function snake(string $string): string
    {
        return StrNormalise::toUnderscoreSeparated($string);
    }
}

if (!function_exists('singular')) {
    function singular(string $string): string
    {
        return StrInflector::toSingular($string);}
}

if (!function_exists('plural')) {
    function plural(string $string): string
    {
        return StrInflector::toPlural($string);
    }
}
