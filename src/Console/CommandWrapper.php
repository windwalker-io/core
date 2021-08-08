<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Attributes\AttributesAccessor;
use Windwalker\Console\Input\InputArgument;
use Windwalker\Console\Input\InputOption;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\DI\Container;
use Windwalker\Utilities\Assert\Assert;

/**
 * The CommandWrapper class.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
class CommandWrapper extends Command implements ContainerAttributeInterface
{
    public const TEMP_NAME = 'windwalker-command-temp-name';

    /**
     * Use another property to store callback that can support extra process flow for this class.
     *
     * @var object|callable
     */
    protected mixed $handler = null;

    protected Container $container;

    public function __construct(
        ?string $name = null,
        ?string $description = null,
        array|string $aliases = [],
        bool $hidden = false
    ) {
        parent::__construct($name ?? self::TEMP_NAME);

        if ($description !== null) {
            $this->setDescription($description);
        }

        $this->setAliases((array) $aliases);
        $this->setHidden($hidden);
    }

    /**
     * @return callable|object
     */
    public function getHandler(): mixed
    {
        return $this->handler;
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
            } elseif (is_callable($this->handler)) {
                $result = ($this->handler)(io: $io);
            } elseif ($this->handler instanceof Command) {
                $result = $this->handler->run($input, $output);
            } else {
                throw new \LogicException(
                    sprintf(
                        'CommandWrapper::$handler should be callable or Command object, %s given',
                        Assert::describeValue($this->handler)
                    )
                );
            }

            if (is_bool($result)) {
                return $result ? 0 : 255;
            }

            return $result ?? 0;
        } catch (\Throwable $e) {
            $io->writeln('<error>An error occurred: ' . $e->getMessage() . '</error>');

            throw $e;
        }
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
        $container = $handler->getContainer();

        return function (array $args, int $options) use ($handler, $container) {
            if (isset($args['name'])) {
                $this->setName($args['name']);

                unset($args['name']);
            }

            $this->handler = $this->configureHandler($handler($args, $options));

            if ($this->handler instanceof \Closure) {
                $this->handler = fn (...$args) => $container->call($this->handler, $args);
            }

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
