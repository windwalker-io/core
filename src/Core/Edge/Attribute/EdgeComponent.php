<?php

declare(strict_types=1);

namespace Windwalker\Core\Edge\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class EdgeComponent
{
    public function __construct(public $name)
    {
    }
}
