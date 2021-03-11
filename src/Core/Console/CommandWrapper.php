<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Console;

use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Attributes\AttributesAccessor;
use Windwalker\Core\Console\Input\InputArgument;
use Windwalker\Core\Console\Input\InputOption;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\DI\Container;

/**
 * The CommandWrapper class.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
class CommandWrapper extends Command implements ContainerAttributeInterface
{
    protected mixed $handler;

    protected Container $container;

    public function __construct(
        ?string $description = null,
        array $aliases = [],
        bool $hidden = false
    ) {
        parent::__construct('temp-name');

        if ($description !== null) {
            $this->setDescription($description);
        }

        $this->setAliases($aliases);
        $this->setHidden($hidden);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new IO($input, $output, $this);

        if ($this->handler instanceof CommandInterface) {
            $result = $this->handler->execute($io);
        } else {
            $result = $this->container->call($this->handler, ['io' => $io]);
        }

        if (is_bool($result)) {
            return $result ? 0 : 255;
        }

        return $result ?? 0;
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        $this->container = $handler->getContainer();

        return function (...$args) use ($handler) {
            if (isset($args['name'])) {
                $this->setName($args['name']);
            }

            $this->handler = $handler(...$args);

            if ($this->handler instanceof CommandInterface) {
                $this->handler->configure($this);
            }

            AttributesAccessor::runAttributeIfExists(
                $this->handler,
                InputArgument::class,
                fn (InputArgument $arg) => $this->getDefinition()->addArgument($arg)
            );

            AttributesAccessor::runAttributeIfExists(
                $this->handler,
                InputOption::class,
                fn (InputOption $option) => $this->getDefinition()->addOption($option)
            );

            return $this;
        };
    }
}
