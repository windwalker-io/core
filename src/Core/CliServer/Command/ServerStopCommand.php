<?php

declare(strict_types=1);

namespace Windwalker\Core\CliServer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\CliServer\CliServerFactory;
use Windwalker\Core\CliServer\Contracts\ServerProcessManageInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\DI\Attributes\Autowire;

/**
 * The ServerStopCommand class.
 */
#[CommandWrapper(
    description: 'Stop Windwalker Server.',
)]
class ServerStopCommand implements CommandInterface
{
    use ServerCommandTrait;

    protected string $name = '';

    /**
     * ServeCommand constructor.
     */
    public function __construct(
        protected ConsoleApplication $app,
        #[Autowire]
        protected CliServerFactory $serverFactory
    ) {
    }

    /**
     * configure
     *
     * @param  Command  $command
     *
     * @return    void
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'server',
            InputArgument::OPTIONAL,
            'The server name with {engine:name} format.',
            'php:main'
        );

        $command->addOption(
            'name',
            null,
            InputOption::VALUE_REQUIRED,
            'The server name.',
            'main'
        );

        $command->addOption(
            'main',
            'm',
            InputOption::VALUE_REQUIRED,
            'The custom server main file.',
        );

        $command->addOption(
            'engine',
            'e',
            InputOption::VALUE_REQUIRED,
            'The CLI Server engine, can be: swoole',
            'php'
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return    int Return 0 is success, 1-255 is failure.
     */
    public function execute(IOInterface $io): int
    {
        $serverName = $io->getArgument('server');

        [$engineName, $name] = explode(':', $serverName) + ['', ''];

        $this->name = $name;

        $engineName = $engineName ?: $io->getOption('engine');

        return match ($engineName) {
            'swoole' => $this->stopSwooleServer($io),
            default => $this->invalidEngine($io, $engineName)
        };
    }

    protected function invalidEngine(IOInterface $io, mixed $engineName): int
    {
        $io->errorStyle()->warning('Server engine: ' . $engineName . ' not supports stop command');

        return Command::FAILURE;
    }

    protected function stopSwooleServer(IOInterface $io): int
    {
        $name = $this->name ?: $io->getOption('name');

        $engine = $this->createEngine('swoole', $name, $io);

        if (!$engine instanceof ServerProcessManageInterface) {
            throw new \LogicException($engine::class . ' should be instance of ' . ServerProcessManageInterface::class);
        }

        if (!$engine->isRunning()) {
            throw new \RuntimeException('Server is not running.');
        }

        if (!$engine->stopServer()) {
            throw new \RuntimeException('Failed to stop server.');
        }

        return Command::SUCCESS;
    }
}
