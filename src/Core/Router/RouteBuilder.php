<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

use FastRoute\RouteParser;
use Windwalker\Utilities\TypeCast;

/**
 * RouteBuilder for FastRoute Parser.
 */
class RouteBuilder
{
    protected RouteParser $parser;

    /**
     * RouteBuilder constructor.
     *
     * @param  RouteParser  $parser
     */
    public function __construct(RouteParser $parser)
    {
        $this->parser = $parser;
    }

    public function parse(string $pattern): array
    {
        return $this->parser->parse($pattern);
    }

    public function build(string $pattern, array $vars): array
    {
        $variants = $this->parse($pattern);
        $variant = $this->findVariant($variants, $vars);

        return $this->compileUri($variant, $vars);
    }

    /**
     * findVariant
     *
     * @param  array  $variants
     * @param  array  $vars
     *
     * @return  array
     */
    protected function findVariant(array $variants, array $vars): array
    {
        $found = 0;

        foreach ($variants as $i => $variant) {
            array_shift($variant);

            $names = array_column($variant, 0);

            if (array_intersect($names, array_keys($vars)) === $names) {
                $found = $i;
            }
        }

        return $variants[$found];
    }

    /**
     * compileUri
     *
     * @param  array  $variant
     * @param  array  $vars
     *
     * @return  array
     */
    protected function compileUri(array $variant, array $vars): array
    {
        $segments = [];

        foreach ($variant as $segment) {
            if (!is_array($segment)) {
                $segments[] = $segment;
                continue;
            }

            [$name] = $segment;

            $var = TypeCast::forceString($vars[$name] ?? '{' . $name . '}');

            $segments[] = urlencode($var);

            unset($vars[$name]);
        }

        $pattern = implode('', $segments);

        if ($vars !== []) {
            $pattern .= '?' . http_build_query($vars);
        }

        // Remove start slash to make this uri relative.
        return [ltrim($pattern, '/'), $vars];
    }
}
