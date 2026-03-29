<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Service\FilterService;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\Filter\Exception\ValidateException;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class Required extends Assert
{
    public function __construct(
        public bool $nullable = false,
        public bool $strict = false
    ) {
            parent::__construct('required', $nullable, $strict);
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        $closure = parent::__invoke($handler);

        return static function () use ($handler, $closure) {
            try {
                return $closure();
            } catch (ValidateException $e) {
                throw ValidateException::create(
                    $e->getValidator(),
                    sprintf(
                        'Field "%s" is required.',
                        $handler->reflector->getName()
                    ),
                    $e->getCode(),
                    $e
                );
            }
        };
    }
}
