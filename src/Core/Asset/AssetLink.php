<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Asset;

use Psr\Http\Message\UriInterface;
use Psr\Link\EvolvableLinkInterface;
use Windwalker\Uri\Uri;

/**
 * The AssetLink class.
 */
class AssetLink implements EvolvableLinkInterface
{
    protected Uri $uri;

    protected array $rels = [];

    protected array $attributes = [];

    /**
     * AssetLink constructor.
     *
     * @param  UriInterface|string  $uri
     */
    public function __construct(UriInterface|string $uri)
    {
        if (!$uri instanceof Uri) {
            $uri = Uri::wrap($uri);
        }

        $this->uri = $uri;
    }

    /**
     * Returns the target of the link.
     *
     * The target link must be one of:
     * - An absolute URI, as defined by RFC 5988.
     * - A relative URI, as defined by RFC 5988. The base of the relative link
     *     is assumed to be known based on context by the client.
     * - A URI template as defined by RFC 6570.
     *
     * If a URI template is returned, isTemplated() MUST return True.
     *
     * @return string
     */
    public function getHref(): string
    {
        return (string) $this->uri;
    }

    /**
     * Returns whether or not this is a templated link.
     *
     * @return bool
     *   True if this link object is templated, False otherwise.
     */
    public function isTemplated(): bool
    {
        return false;
    }

    /**
     * Returns the relationship type(s) of the link.
     *
     * This method returns 0 or more relationship types for a link, expressed
     * as an array of strings.
     *
     * @return string[]
     */
    public function getRels(): array
    {
        return $this->rels;
    }

    /**
     * Returns a list of attributes that describe the target URI.
     *
     * @return array
     *   A key-value list of attributes, where the key is a string and the value
     *  is either a PHP primitive or an array of PHP strings. If no values are
     *  found an empty array MUST be returned.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Returns an instance with the specified href.
     *
     * @param  string|\Stringable  $href
     *       The href value to include.  It must be one of:
     *       - An absolute URI, as defined by RFC 5988.
     *       - A relative URI, as defined by RFC 5988. The base of the relative link
     *       is assumed to be known based on context by the client.
     *       - A URI template as defined by RFC 6570.
     *       - An object implementing __toString() that produces one of the above
     *       values.
     *
     * An implementing library SHOULD evaluate a passed object to a string
     * immediately rather than waiting for it to be returned later.
     *
     * @return static
     */
    public function withHref(\Stringable|string $href)
    {
        $new = clone $this;
        $new->uri = Uri::wrap((string) $href);

        return $new;
    }

    /**
     * Returns an instance with the specified relationship included.
     *
     * If the specified rel is already present, this method MUST return
     * normally without errors, but without adding the rel a second time.
     *
     * @param  string  $rel
     *   The relationship value to add.
     *
     * @return static
     */
    public function withRel(string $rel)
    {
        $new = clone $this;

        if (!in_array($rel, $new->rels, true)) {
            $new->rels[] = $rel;
        }

        return $new;
    }

    /**
     * Returns an instance with the specified relationship excluded.
     *
     * If the specified rel is already not present, this method MUST return
     * normally without errors.
     *
     * @param  string  $rel
     *   The relationship value to exclude.
     *
     * @return static
     */
    public function withoutRel(string $rel)
    {
    }

    /**
     * Returns an instance with the specified attribute added.
     *
     * If the specified attribute is already present, it will be overwritten
     * with the new value.
     *
     * @param  string                                   $attribute
     *   The attribute to include.
     * @param  string|\Stringable|int|float|bool|array  $value
     *   The value of the attribute to set.
     *
     * @return static
     */
    public function withAttribute(string $attribute, float|int|\Stringable|bool|array|string $value)
    {
    }

    /**
     * Returns an instance with the specified attribute excluded.
     *
     * If the specified attribute is not present, this method MUST return
     * normally without errors.
     *
     * @param  string  $attribute
     *   The attribute to remove.
     *
     * @return static
     */
    public function withoutAttribute(string $attribute)
    {
    }
}
