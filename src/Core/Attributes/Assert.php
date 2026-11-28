<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Service\FilterService;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\Filter\Exception\ValidateException;
use Windwalker\Filter\ValidatorInterface;

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
 *  - closure: fn (mixed $value): bool - falsy return fails validation
 *  - ValidatorInterface instance, e.g. new Regex('/^\d+$/') - test() returning false fails validation
 *
 * Multiple commands can be passed and are validated in order.
 *
 * Options (bool, default nullable: true, strict: false):
 *  - Named: nullable: bool, strict: bool
 *  - Positional: after the commands, in order nullable then strict
 *
 * Examples:
 *  - #[Assert('int')]                                    Integer, null skips validation
 *  - #[Assert('required', nullable: false)]              Null fails (required rejects null)
 *  - #[Assert('int', strict: true)]                      Strict type check: '5' fails, 5 passes
 *  - #[Assert('int', false, true)]                       Same as nullable: false, strict: true
 *  - #[Assert('required', is_numeric(...))]              Required, and must be numeric
 *  - #[Assert('string', fn (string $v) => strlen($v) <= 20)]
 *                                                        String with a closure rule
 *  - #[Assert('required', is_numeric(...), new Regex('/^\d+$/'))]
 *                                                        Closure and validator instance in order
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Assert implements ContainerAttributeInterface
{
    public array $commands = [];

    public bool $nullable = true;

    public bool $strict = false;

    public function __construct(
        string|array|\Closure|ValidatorInterface|bool ...$commands
    ) {
        $options = [];
        $positional = ['nullable', 'strict'];

        foreach ($commands as $key => $command) {
            if (is_string($key)) {
                if (!is_bool($command) || !in_array($key, ['nullable', 'strict'], true)) {
                    throw new \InvalidArgumentException(sprintf('Invalid Assert option "%s".', $key));
                }

                $name = $key;
            } elseif (is_bool($command)) {
                // Positional bools keep the legacy (command, nullable, strict) order.
                $name = array_shift($positional);

                if ($name === null) {
                    throw new \InvalidArgumentException('Assert accepts at most two positional bool options.');
                }
            } else {
                $this->commands[] = $command;
                continue;
            }

            if (array_key_exists($name, $options)) {
                throw new \InvalidArgumentException(sprintf('Assert option "%s" is set more than once.', $name));
            }

            $options[$name] = $command;
        }

        $this->nullable = $options['nullable'] ?? true;
        $this->strict = $options['strict'] ?? false;
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function () use ($handler) {
            $value = $handler();

            if ($this->nullable && $value === null) {
                return null;
            }

            try {
                $service = $handler->container->get(FilterService::class);

                foreach ($this->commands as $command) {
                    if ($command instanceof \Closure || $command instanceof ValidatorInterface) {
                        $service->validateWith($value, $command);
                    } else {
                        $service->validate($value, $command, $this->strict);
                    }
                }

                return $value;
            } catch (ValidateException $e) {
                $debug = $handler->container->get(ApplicationInterface::class)->isDebug();
                $name = $handler->reflector->getName();

                $message = sprintf('Invalid type of field "%s"', $name);

                if ($debug) {
                    $message .= ' - ' . $e->getMessage();
                }

                throw ValidateException::create(
                    $e->getValidator(),
                    $message,
                    400,
                    $e
                );
            }
        };
    }
}
