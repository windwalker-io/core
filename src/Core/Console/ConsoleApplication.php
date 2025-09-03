<?php

declare(strict_types=1);

namespace Windwalker\Core\Console;

use Composer\InstalledVersions;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Application as SymfonyApp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\AppClient;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\ApplicationTrait;
use Windwalker\Core\Application\RootApplicationInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\DI\RequestBootableProviderInterface;
use Windwalker\Core\Event\SymfonyDispatcherWrapper;
use Windwalker\Core\Events\Console\ConsoleLogEvent;
use Windwalker\Core\Events\Console\ErrorMessageOutputEvent;
use Windwalker\Core\Events\Console\MessageOutputEvent;
use Windwalker\Core\Manager\LoggerManager;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\Core\Provider\ConsoleProvider;
use Windwalker\Core\Provider\WebProvider;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Filesystem\Path;
use Windwalker\Http\Factory\ServerRequestFactory;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Uri\Uri;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Str;

use function Windwalker\DI\create;

/**
 * The ConsoleApplication class.
 */
class ConsoleApplication extends SymfonyApp implements RootApplicationInterface
{
    use ApplicationTrait;

    protected bool $booted = false;

    protected ?OutputInterface $output = null;

    public ?WebAppSimulator $webSimulator = null;

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
     * @return  void
     *
     * @throws DefinitionException
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->prepareBoot();

        $this->setDispatcher(new SymfonyDispatcherWrapper($this->getEventDispatcher()));

        // Prepare child
        $container = $this->getContainer();
        $container->registerServiceProvider(new AppProvider($this));

        $this->registerAllConfigs($container);
        $container->registerServiceProvider(new ConsoleProvider($this));

        // Commands
        $commands = Arr::flatten((array) $this->config('commands'), ':');

        $this->registerCommands($commands);

        $this->registerListeners($container);

        $this->bootProvidersBeforeRequest($container);

        $this->registerEvents();

        $this->on(ConsoleTerminateEvent::class, fn($event) => $this->terminating($container));

        $this->booting($container->createChild());

        $this->logger ??= $this->getLogger();

        // $container->clearCache();

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
     * @inheritDoc
     */
    #[\Override]
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output): int
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
                $tag = match ($event->type) {
                    'success', 'green' => '<info>%s</info>',
                    'warning', 'yellow' => '<comment>%s</comment>',
                    'info', 'blue' => '<option>%s</option>',
                    'error', 'danger', 'red' => '<error>%s</error>',
                    default => '%s',
                };

                foreach ($event->messages as $message) {
                    $time = gmdate('Y-m-d H:i:s');

                    $output->writeln(sprintf('[%s] ' . $tag, $time, $message));
                }
            }
        );

        $this->on(MessageOutputEvent::class, fn(MessageOutputEvent $event) => $event->writeWith($output));
        $this->on(ErrorMessageOutputEvent::class, fn(ErrorMessageOutputEvent $event) => $event->writeWith($output));
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

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        try {
            $exitCode = parent::doRun($input, $output);

            if ($input instanceof ArgvInput) {
                $this->log(
                    'windwalker ' . implode(' ', $input->getRawTokens()),
                    compact('exitCode'),
                    level: LogLevel::INFO
                );
            }

            return $exitCode;
        } catch (\Throwable $e) {
            if ($input instanceof ArgvInput) {
                $this->log(
                    'windwalker ' . implode(' ', $input->getRawTokens()),
                    [
                        'error' => $e->getMessage(),
                        'code' => $e->getCode()
                    ],
                    level: LogLevel::ERROR
                );
            }

            throw $e;
        }
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
     * @throws Exception|ExceptionInterface
     */
    public function runCommand(
        string|Command $command,
        array|InputInterface|IOInterface $input,
        ?OutputInterface $output = null
    ): int {
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

    public function prepareWebSimulator(string|UriInterface|null $uri = null, ?string $docroot = null): WebAppSimulator
    {
        $uri ??= Uri::wrap($this->config('web_simulator.uri') ?: 'https://local.dev');
        $docroot = Path::normalize(
            $docroot ?? $this->config('web_simulator.docroot') ?? Path::findRoot(__DIR__)
        );
        $index = $this->path('@public/index.php');

        $script = Str::removeLeft($index, $docroot);
        $script = Path::clean($script, '/');

        return $this->prepareWebSimulatorByRequest(ServerRequestFactory::createFromUri($uri, $script));
    }

    public function prepareWebSimulatorByRequest(?ServerRequestInterface $request = null): WebAppSimulator
    {
        if ($this->webSimulator) {
            return $this->webSimulator;
        }

        $app = new WebAppSimulator($container = $this->getContainer());

        $container->share(WebApplication::class, $app);

        $container->registerServiceProvider(new WebProvider($app));

        // Override back ApplicationInterface
        $container->alias(ApplicationInterface::class, static::class);

        $container->share(ServerRequest::class, $request);

        return $this->webSimulator = $app;
    }

    /**
     * @inheritDoc
     */
    public function getClient(): AppClient
    {
        return AppClient::CONSOLE;
    }

    protected function getProcessOutputCallback(?OutputInterface $output = null): callable
    {
        $output ??= new ConsoleOutput();
        $err = $output->getErrorOutput();

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

    protected function bootProvidersBeforeRequest(Container $container)
    {
        foreach ($this->providers as $provider) {
            if ($provider instanceof RequestBootableProviderInterface) {
                $provider->bootBeforeRequest($container);
            }
        }
    }

    public function log(\Stringable|string $message, array $context = [], string $level = LogLevel::INFO): static
    {
        $this->logger ??= $this->getLogger();

        $this->logger->log($level, $message, $context);

        return $this;
    }

    protected function getLogger(): LoggerInterface
    {
        if ($this->container->has(LoggerInterface::class, tag: 'system/console')) {
            return $this->container->get(LoggerInterface::class, tag: 'system/console');
        }

        return new NullLogger();
    }
}
