<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Pagination;

use JsonSerializable;
use Windwalker\Core\Router\RouteUri;

/**
 * The PageItem class.
 */
class PageItem implements JsonSerializable
{
    /**
     * PageItem constructor.
     *
     * @param  RouteUri|null  $route
     * @param  string         $name
     * @param  int            $page
     */
    public function __construct(public string $name, public int $page, public ?RouteUri $route = null)
    {
        //
    }

    public function toLink(): string
    {
        return (string) $this->route;
    }

    public function __toString(): string
    {
        return $this->toLink();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
