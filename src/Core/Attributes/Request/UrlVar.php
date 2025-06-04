<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes\Request;

use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\Utilities\Arr;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class UrlVar extends Input
{
    protected function getValueFromRequest(AppRequestInterface $appRequest, string $name): mixed
    {
        $values = $appRequest->getUrlVars();

        return Arr::get($values, $name, $this->delimiter);
    }
}
