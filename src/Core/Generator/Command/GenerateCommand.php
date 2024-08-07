<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\Command;

use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Console\SubCommandAwareInterface;
use Windwalker\DI\Exception\DefinitionException;

/**
 * The GenerateCommand class.
 */
#[CommandWrapper(
    description: 'Generate files.',
    aliases: 'g'
)]
class GenerateCommand implements CommandInterface, SubCommandAwareInterface, CompletionAwareInterface
{
    protected ?array $subCommands = null;

    /**
     * GenerateCommand constructor.
     */
    public function __construct(protected ConsoleApplication $app)
    {
    }

    /**
     * configure
     *
     * @param  Command  $command
     *
     * @return  void
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'options',
            InputArgument::IS_ARRAY,
            'The generate task.',
        );

        $command->addOption(
            'about',
            null,
            InputOption::VALUE_NONE,
            'Describe the task.',
        );
    }

    public function configureSubCommand(Command $command, InputInterface $input): void
    {
        $command->addOption(
            'f',
            null,
            InputOption::VALUE_OPTIONAL,
            'Force override existing files.'
        );

        $argv = $_SERVER['argv'];
        $task = $argv[2] ?? '';

        if ($task) {
            $subCommand = $this->getSubCommand($task);

            if ($subCommand) {
                $subCommand = $this->resolveSubCommand($task, $subCommand);

                $definition = $command->getDefinition();
                $definition->setArguments([]);
                $definition->addArgument(
                    new InputArgument(
                        'task',
                        InputArgument::REQUIRED,
                        'The task name.'
                    )
                );
                $definition->addArguments($subCommand->getDefinition()->getArguments());
                $definition->addOptions($subCommand->getDefinition()->getOptions());

                $command->setDefinition($definition);

                $appDefinition = $this->app->getDefinition();
                $options = $appDefinition->getOptions();

                $appDefinition->setOptions($options);

                try {
                    $input->bind($definition);
                } catch (RuntimeException) {
                    // No actions that validation will run later
                }
            } else {
                throw new \InvalidArgumentException("Generate task `$task` not found");
            }
        } else {
            $definition = $command->getDefinition();
            $definition->setArguments([]);
            $definition->addArgument(
                new InputArgument(
                    'task',
                    InputArgument::OPTIONAL,
                    'The task name.'
                )
            );

            $command->setDefinition($definition);

            try {
                $input->bind($definition);
            } catch (RuntimeException) {
                // No actions that validation will run later
            }
        }
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     */
    public function execute(IOInterface $io): int
    {
        $task = $io->getArgument('task');

        if (!$task) {
            $this->showList($io);

            return 0;
        }

        if ($io->getOption('about') && $command = $this->getSubCommand($task)) {
            $this->aboutTask($io, $task, $command);

            return 0;
        }

        /** @var Command $subCommand */
        $subCommand = $this->resolveSubCommand($task, $this->getSubCommand($task));

        $argv = $_SERVER['argv'];
        array_shift($argv);

        $definition = $io->getWrapperCommand()->getDefinition();
        $args = $definition->getArguments();

        unset($args['command']);
        $definition->setArguments($args);

        include_once __DIR__ . '/../generator-helpers.php';

        return $subCommand->run(new ArgvInput($argv, $definition), $io->getOutput());
    }

    protected function aboutTask(IOInterface $io, string $task, mixed $command): void
    {
        $command = $this->resolveSubCommand($task, $command);

        $helper = new DescriptorHelper();
        $helper->describe($io->getOutput(), $command);
    }

    protected function showList(IOInterface $io): void
    {
        $subApp = $this->getSubApp();
        $subApp->addCommands($this->resolveAllSubCommands());

        $inputDefinition = $io->getWrapperCommand()->getDefinition();
        $appDefinition = $subApp->getDefinition();
        $appDefinition->addOption($inputDefinition->getOption('about'));
        $appDefinition->addOption($inputDefinition->getOption('f'));

        $helper = new DescriptorHelper();
        $helper->describe($io->getOutput(), $subApp);
    }

    protected function getSubApp(): Application
    {
        $subApp = new Application(
            $this->app->getName(),
            $this->app->getVersion()
        );

        $subApp->get('help')->setHidden(true);
        $subApp->get('list')->setHidden(true);

        return $subApp;
    }

    /**
     * resolveSubCommand
     *
     * @param  string  $name
     * @param  string  $command
     *
     * @return  Command
     *
     * @throws DefinitionException
     */
    protected function resolveSubCommand(string $name, string $command): object
    {
        /** @var Command $cmd */
        $cmd = $this->app->service($command);
        $cmd->setName($name);
        $cmd->setApplication($this->app);

        return $cmd;
    }

    /**
     * resolveAllSubCommands
     *
     * @return  array<Command>
     */
    protected function resolveAllSubCommands(): array
    {
        $commands = [];

        foreach ($this->getSubCommands() as $name => $subCommand) {
            $commands[$name] = $this->resolveSubCommand($name, $subCommand);
        }

        return $commands;
    }

    protected function getSubCommand(string $name): ?string
    {
        return $this->getSubCommands()[$name] ?? null;
    }

    protected function getSubCommands(): array
    {
        return $this->subCommands ??= $this->app->config('generator.commands');
    }

    /**
     * @inheritDoc
     */
    public function completeOptionValues($optionName, CompletionContext $context)
    {
    }

    /**
     * @inheritDoc
     */
    public function completeArgumentValues($argumentName, CompletionContext $context)
    {
        if ($argumentName === 'options' && $context->getWordIndex() === 2) {
            return array_keys($this->getSubCommands());
        }
    }
}
