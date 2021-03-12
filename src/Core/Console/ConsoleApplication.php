<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Console;

use Composer\InstalledVersions;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\Console\Application as SymfonyApp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\ApplicationTrait;
use Windwalker\Core\Event\MessageEvent;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Utilities\Arr;

use function Windwalker\DI\create;

/**
 * The ConsoleApplication class.
 */
class ConsoleApplication extends SymfonyApp implements ApplicationInterface
{
    use ApplicationTrait;

    protected bool $booted = false;

    /**
     * ConsoleApplication constructor.
     *
     * @param  Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        parent::__construct(
            'Windwalker Console',
            InstalledVersions::getPrettyVersion('windwalker/core')
        );
    }

    /**
     * boot
     *
     * @return  void
     *
     * @throws DefinitionException
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Prepare child
        $container = $this->getContainer();
        $container->registerServiceProvider(new AppProvider($this));

        $container->registerByConfig($this->config('di') ?? []);

        foreach ($this->config as $service => $config) {
            if (!is_array($config)) {
                throw new \LogicException("Config: '{$service}' must be array");
            }

            $container->registerByConfig($config ?: []);
        }

        // Commands
        $commands = Arr::flatten($commands = (array) $this->config('commands'), ':');

        $this->registerCommands($commands);

        $this->booted = true;
    }

    public function registerCommands(array $commands): void
    {
        $container = $this->getContainer();

        foreach ($commands as $name => &$command) {
            if (is_string($command)) {
                // Handle class command
                if (class_exists($command)) {
                    $container->bind($command, create($command, name: $name));
                    continue;
                }

                if (is_file($command)) {
                    $command = include $command;
                }
            }

            // Object and closure
            if (is_object($command)) {
                $container->set(
                    $id = 'command:' . $name,
                    function (Container $container) use ($name, $command) {
                        /** @var CommandWrapper $cmd */
                        $cmd = $container->getAttributesResolver()->decorateObject($command);

                        return $cmd->setName($name);
                    }
                );

                $command = $id;
            }
        }

        unset($command);

        $this->setCommandLoader(
            new ContainerCommandLoader(
                $container,
                $commands
            )
        );
    }

    /**
     * Configures the input and output instances based on the user arguments and options.
     *
     * @param  InputInterface   $input
     * @param  OutputInterface  $output
     */
    protected function configureIO(InputInterface $input, OutputInterface $output): void
    {
        parent::configureIO($input, $output);

        $this->on(
            MessageEvent::class,
            function (MessageEvent $event) use ($input, $output) {
                $tag = match ($event->getType()) {
                    'success', 'green' => '<info>%s</info>',
                    'warning', 'yellow' => '<comment>%s</comment>',
                    'info', 'blue' => '<option>%s</option>',
                    'error', 'danger', 'red' => '<error>%s</error>',
                    default => '%s',
                };

                foreach ($event->getMessages() as $message) {
                    $time = gmdate('Y-m-d H:i:s');

                    $output->writeln(sprintf('[%s] ' . $tag, $time, $message));
                }
            }
        );
    }

    public function addMessage(string|array $messages, ?string $type = null): static
    {
        $this->emit(new MessageEvent($messages, $type));

        return $this;
    }

    /**
     * runCommand
     *
     * @param  string|Command  $command
     * @param  IOInterface     $io
     *
     * @return  int
     *
     * @throws \Exception
     */
    public function runCommand(string|Command $command, IOInterface $io): int
    {
        if (is_string($command)) {
            if (str_contains($command, '\\')) {
                $commands = (array) $this->config('console.commands');

                $i = array_search(
                    $command,
                    array_keys($commands),
                    true
                );

                $command = array_values($commands)[$i];
            }

            $command = $this->find($command);
        }

        // $io->getInput()->bind($command->getDefinition());

        // show($io->getInput());

        return $command->run($io->getInput(), $io->getOutput());
    }

    /**
     * @inheritDoc
     */
    #[NoReturn]
    public function close(
        mixed $return = 0
    ): void {
        exit((int) $return);
    }

    /**
     * @inheritDoc
     */
    public function getClient(): string
    {
        return static::CLIENT_CONSOLE;
    }

    protected function getProcessOutputCallback(): callable
    {
        $output = new ConsoleOutput();
        $err    = $output->getErrorOutput();

        return static function ($type, $buffer) use ($err, $output) {
            if (Process::ERR === $type) {
                $err->write($buffer, false);
            } else {
                $output->write($buffer);
            }
        };
    }
}
