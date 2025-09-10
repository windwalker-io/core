<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Service\FilterService;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\Filter\Exception\ValidateException;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class Assert implements ContainerAttributeInterface
{
    public function __construct(public string|array $command, public bool $nullable = true, public bool $strict = false)
    {
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function () use ($handler) {
            $value = $handler();

            if ($this->nullable && $value === null) {
                return null;
            }

            try {
                $handler->getContainer()
                    ->get(FilterService::class)
                    ->validate($value, $this->command, $this->strict);

                return $value;
            } catch (ValidateException $e) {
                $debug = $handler->getContainer()->get(ApplicationInterface::class)->isDebug();
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
