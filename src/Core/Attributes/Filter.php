<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\Core\Service\FilterService;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

/**
 * syntaxes:
 *  - abs
 *  - alnum
 *  - cmd
 *  - email
 *  - url
 *  - words
 *  - ip
 *  - ipv4
 *  - ipv6
 *  - neg
 *  - raw
 *  - range(min=int, max=int)
 *  - clamp(min=int, max=int)
 *  - length(max=int, [utf8])
 *  - regex(regex=string, type='match'|'replace')
 *  - required
 *  - default(value=mixed)
 *  - func(callback)
 *  - string([strict])
 *  - int([strict])
 *  - float([strict])
 *  - array([strict])
 *  - bool([strict])
 *  - object([strict])
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class Filter implements ContainerAttributeInterface
{
    public function __construct(
        public string|array $command
    ) {
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function () use ($handler) {
            $value = $handler();

            return $handler->container
                ->get(FilterService::class)
                ->filter($value, $this->command);
        };
    }
}
