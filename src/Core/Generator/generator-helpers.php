<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

use Windwalker\Utilities\StrInflector;
use Windwalker\Utilities\StrNormalize;

if (!function_exists('kebab')) {
    function kebab(string $string): string
    {
        return StrNormalize::toKebabCase($string);
    }
}

if (!function_exists('camel')) {
    function camel(string $string): string
    {
        return StrNormalize::toCamelCase($string);
    }
}

if (!function_exists('pascal')) {
    function pascal(string $string): string
    {
        return StrNormalize::toPascalCase($string);
    }
}

if (!function_exists('snake')) {
    function snake(string $string): string
    {
        return StrNormalize::toSnakeCase($string);
    }
}

if (!function_exists('dot')) {
    function dot(string $string): string
    {
        return strtolower(StrNormalize::toDotSeparated($string));
    }
}

if (!function_exists('singular')) {
    function singular(string $string): string
    {
        return StrInflector::toSingular($string);
    }
}

if (!function_exists('plural')) {
    function plural(string $string): string
    {
        return StrInflector::toPlural($string);
    }
}
