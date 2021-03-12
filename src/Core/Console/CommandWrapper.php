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
    protected mixed $handler = null;

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
     * getIO
     *
     * @param  InputInterface   $input
     * @param  OutputInterface  $output
     *
     * @return  IOInterface
     */
    protected function getIO(InputInterface $input, OutputInterface $output): IOInterface
    {
        return new IO($input, $output, $this);
    }

    /**
     * Interacts with the user.
     *
     * This method is executed before the InputDefinition is validated.
     * This means that this is the only place where the command can
     * interactively ask for values of missing required arguments.
     *
     * @param  InputInterface   $input
     * @param  OutputInterface  $output
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if ($this->handler instanceof InteractInterface) {
            $this->handler->interact($this->getIO($input, $output));
        }
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getIO($input, $output);

        try {
            if ($this->handler instanceof CommandInterface) {
                $result = $this->handler->execute($io);
            } else {
                $result = $this->container->call($this->handler, ['io' => $io]);
            }
        } catch (\Throwable $e) {
            $io->writeln('<error>An error occurred: ' . $e->getMessage() . '</error>');

            throw $e;
        }

        if (is_bool($result)) {
            return $result ? 0 : 255;
        }

        return $result ?? 0;
    }

    /**
     * Invoke handler.
     *
     * @param  AttributeHandler  $handler
     *
     * @return  callable
     */
    public function __invoke(AttributeHandler $handler): callable
    {
        $this->container = $handler->getContainer();

        return function (...$args) use ($handler) {
            if (isset($args['name'])) {
                $this->setName($args['name']);

                unset($args['name']);
            }

            $this->handler = $this->configureHandler($handler(...$args));

            return $this;
        };
    }

    /**
     * configureInnerCommand
     *
     * @param  callable|CommandInterface  $handler
     *
     * @return callable|CommandInterface
     */
    protected function configureHandler(callable|CommandInterface $handler): callable|CommandInterface
    {
        if ($handler instanceof CommandInterface) {
            $handler->configure($this);
        }

        // Register arguments
        AttributesAccessor::runAttributeIfExists(
            $handler,
            InputArgument::class,
            fn(InputArgument $arg) => $this->getDefinition()->addArgument($arg)
        );

        // Register options
        AttributesAccessor::runAttributeIfExists(
            $handler,
            InputOption::class,
            fn(InputOption $option) => $this->getDefinition()->addOption($option)
        );

        return $handler;
    }
}
