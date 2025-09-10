<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Attribute;
use Windwalker\Core\Http\AppRequest;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\Http\Exception\HttpRequestException;

/**
 * The Method class.
 */
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class Method implements ContainerAttributeInterface
{
    /**
     * @var array|string[]
     */
    protected array $methods;

    public function __construct(string ...$methods)
    {
        $this->methods = array_map('strtoupper', $methods);
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function (...$args) use ($handler) {
            $container = $handler->container;
            $request = $container->get(AppRequest::class);

            $method = strtoupper($request->getMethod());

            if (!in_array($method, $this->methods, true)) {
                throw new HttpRequestException('Method not allowed', 400);
            }

            return $handler(...$args);
        };
    }
}
