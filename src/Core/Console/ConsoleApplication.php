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
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\ApplicationTrait;
use Windwalker\Core\Event\SymfonyDispatcherWrapper;
use Windwalker\Core\Events\Console\ConsoleLogEvent;
use Windwalker\Core\Events\Console\ErrorMessageOutputEvent;
use Windwalker\Core\Events\Console\MessageOutputEvent;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\Core\Provider\ConsoleProvider;
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

    protected ?OutputInterface $output = null;

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

        $this->setDispatcher(new SymfonyDispatcherWrapper($this->getEventDispatcher()));
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

        foreach ($this->config->getIterator(false) as $service => $config) {
            if (!is_array($config) || !($config['enabled'] ?? true)) {
                continue;
                // throw new \LogicException("Config: '{$service}' must be array");
            }

            $container->registerByConfig($config ?: []);
        }

        $container->registerServiceProvider(new ConsoleProvider($this));

        // Commands
        $commands = Arr::flatten((array) $this->config('commands'), ':');

        $this->registerCommands($commands);

        $this->registerEvents();

        $this->on(ConsoleTerminateEvent::class, fn ($event) => $this->terminating($container));

        $this->booting($container->createChild());

        $container->clearCache();

        $this->booted = true;
    }

    /**
     * Your booting logic.
     *
     * @param  Container  $container
     *
     * @return  void
     */
    protected function booting(Container $container): void
    {
        //
    }

    public function registerCommands(array $commands): void
    {
        $commandsMap = [];

        $container = $this->getContainer();

        foreach ($commands as $name => $command) {
            if (is_string($command)) {
                // Handle class command
                if (class_exists($command)) {
                    $container->bind($command, create($command, name: $name));
                    $commandsMap[$name] = $command;
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
                    function (Container $container) use (&$name, $command) {
                        /** @var CommandWrapper $cmd */
                        $cmd = $container->getAttributesResolver()->decorateObject($command);

                        if ($cmd->getName() !== CommandWrapper::TEMP_NAME) {
                            // If CommandWrapper name set, use command name
                            $name = $cmd->getName();
                        } else {
                            // Otherwise use key as name.
                            $cmd->setName($name);
                        }

                        return $cmd;
                    }
                );

                $commandsMap[$name] = $id;
            }
        }

        $this->setCommandLoader(
            new ContainerCommandLoader(
                $container,
                $commandsMap
            )
        );
    }

    /**
     * Runs the current command.
     *
     * If an event dispatcher has been attached to the application,
     * events are also dispatched during the life-cycle of the command.
     *
     * @return int 0 if everything went fine, or an error code
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        if ($command instanceof CommandWrapper) {
            $handler = $command->getHandler();

            if ($handler instanceof SubCommandAwareInterface) {
                $handler->configureSubCommand($command, $input);
            }
        }

        return parent::doRunCommand($command, $input, $output);
    }

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output ?? new ConsoleOutput();
    }

    protected function registerEvents(): void
    {
        $output = $this->getOutput();

        $this->on(
            ConsoleLogEvent::class,
            function (ConsoleLogEvent $event) use ($output) {
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

        $this->on(MessageOutputEvent::class, fn (MessageOutputEvent $event) => $event->writeWith($output));
        $this->on(ErrorMessageOutputEvent::class, fn (ErrorMessageOutputEvent $event) => $event->writeWith($output));
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

        static::addColors($output);
    }

    public static function addColors(OutputInterface $output): void
    {
        // $formatter = $output->getFormatter();
        //
        // $outputStyle = new OutputFormatterStyle(null, '#666', ['bold', 'blink']);
        // $formatter->setStyle('gray', $outputStyle);
    }

    public function addMessage(string|array $messages, ?string $type = null): static
    {
        $this->emit(new ConsoleLogEvent($messages, $type));

        return $this;
    }

    /**
     * runCommand
     *
     * @param  string|Command                    $command
     * @param  array|InputInterface|IOInterface  $input
     * @param  OutputInterface|null              $output
     *
     * @return  int
     *
     * @throws \Exception
     */
    public function runCommand(string|Command $command, array|InputInterface|IOInterface $input, ?OutputInterface $output = null): int
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

        if ($input instanceof IOInterface) {
            $output = $input->getOutput();
            $input = $input->getInput();
        } elseif (is_array($input)) {
            $input = new ArrayInput($input);
        }

        if ($output === null) {
            $output = $this->getOutput();
        }

        return $command->run($input, $output);
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

    protected function getProcessOutputCallback(?OutputInterface $output = null): callable
    {
        $output ??= new ConsoleOutput();
        $err    = $output->getErrorOutput();

        return static function ($type, $buffer) use ($err, $output) {
            if (Process::ERR === $type) {
                $err->write($buffer, false);
            } else {
                $output->write($buffer);
            }
        };
    }

    protected function terminating(Container $container): void
    {
        //
    }
}
