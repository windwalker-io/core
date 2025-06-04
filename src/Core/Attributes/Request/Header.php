<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes\Request;

use Windwalker\Core\Application\Context\AppRequestInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class Header extends Input
{
    public function __construct(string $name, mixed $default = null)
    {
        parent::__construct($name, $default);
    }

    protected function getValueFromRequest(AppRequestInterface $appRequest, string $name): string
    {
        return $appRequest->getHeader($name);
    }
}
