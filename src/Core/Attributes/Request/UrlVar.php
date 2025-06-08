<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes\Request;

/**
 * An alias of RouteParam.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class UrlVar extends RouteParam
{
    //
}
