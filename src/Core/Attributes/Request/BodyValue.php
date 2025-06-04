<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes\Request;

use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Utilities\Arr;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class BodyValue extends Input
{
    protected function getValueFromRequest(AppRequestInterface $appRequest, string $name): mixed
    {
        if (!$appRequest instanceof AppRequest) {
            throw new \RuntimeException(
                sprintf(
                    'BodyValue attribute can only be used with %s, %s given.',
                    AppRequest::class,
                    get_class($appRequest)
                )
            );
        }

        $values = $appRequest->getBodyValues();

        return Arr::get($values, $name, $this->delimiter);
    }
}
