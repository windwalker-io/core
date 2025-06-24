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
        public string $delimiter = '.'
    ) {
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function () use ($handler) {
            /** @var \ReflectionParameter $ref */
            $ref = $handler->getReflector();
            $field = $this->name ?? $ref->getName();
            $isOptional = $ref->isOptional();

            $default = $this->default ?? ($isOptional ? $ref->getDefaultValue() : null);

            $value = $handler()
                ?? $this->getValueFromRequest($handler->getContainer()->get(AppRequestInterface::class), $field)
                ?? $default;

            if ($value === null && !$isOptional && !$ref->allowsNull()) {
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

    protected function getValueFromRequest(AppRequestInterface $appRequest, string $name): mixed
    {
        return $appRequest->input()->getDeep($name, $this->delimiter);
    }
}
