<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes\Request;

use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class Input implements ContainerAttributeInterface
{
    public function __construct(
        public ?string $name = null,
        public mixed $default = null,
        public string $delimiter = '.',
    ) {
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function () use ($handler) {
            /** @var \ReflectionParameter|\ReflectionProperty $ref */
            $ref = $handler->reflector;
            $field = $this->name ?? $ref->getName();
            $isOptional = $ref instanceof \ReflectionParameter ? $ref->isOptional() : false;

            $default = $this->default ?? ($isOptional ? $ref->getDefaultValue() : null);

            $value = $handler()
                ?? $this->getValueFromRequest($handler->container->get(AppRequestInterface::class), $field)
                ?? $default;

            if ($value === null && !$isOptional && !static::allowsNull($ref)) {
                throw new \RuntimeException(
                    sprintf(
                        'Field "%s" is missing in request.',
                        $field
                    )
                );
            }

            return $value;
        };
    }

    protected static function allowsNull(\ReflectionProperty|\ReflectionParameter $ref): bool
    {
        if ($ref instanceof \ReflectionParameter) {
            return $ref->allowsNull();
        }

        return $ref->getType()?->allowsNull() ?? true;
    }

    protected function getValueFromRequest(AppRequestInterface $appRequest, string $name): mixed
    {
        return $appRequest->input()->getDeep($name, $this->delimiter);
    }
}
