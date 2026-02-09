<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Service\FilterService;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\Filter\Exception\ValidateException;

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
class Assert implements ContainerAttributeInterface
{
    public function __construct(
        public string|array $command,
        public bool $nullable = true,
        public bool $strict = false
    ) {
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function () use ($handler) {
            $value = $handler();

            if ($this->nullable && $value === null) {
                return null;
            }

            try {
                $handler->container
                    ->get(FilterService::class)
                    ->validate($value, $this->command, $this->strict);

                return $value;
            } catch (ValidateException $e) {
                $debug = $handler->container->get(ApplicationInterface::class)->isDebug();
                $name = $handler->reflector->getName();

                $message = sprintf("Invalid type of field \"%s\"", $name);

                if ($debug) {
                    $message .= ' - ' . $e->getMessage();
                }

                throw new \RuntimeException(
                    $message,
                    400,
                    $e
                );
            }
        };
    }
}
