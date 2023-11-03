<?php

/**
 * Part of starter project.
 *
 * @copyright      Copyright (C) 2021 LYRASOFT.
 * @license        MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\CliServer\CliServerFactory;
use Windwalker\Core\CliServer\Contracts\ServerProcessManageInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\Utilities\TypeCast;

/**
 * The ServeCommand class.
 */
#[CommandWrapper(
    description: 'Start Windwalker Server.',
    aliases: ['dev:serve']
)]
class ServerStartCommand implements CommandInterface, SignalableCommandInterface
{
    use ServerCommandTrait;

    protected IOInterface $io;

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
            'The CLI Server engine, can be: php|swoole',
            'php'
        );

        $command->addOption(
            'docroot',
            't',
            InputOption::VALUE_REQUIRED,
            'The docroot dir path.'
        );

        $command->addOption(
            'host',
            null,
            InputOption::VALUE_REQUIRED,
            'The server host.',
            'localhost'
        );

        $command->addOption(
            'port',
            'p',
            InputOption::VALUE_REQUIRED,
            'The server port.'
        );

        $command->addOption(
            'app',
            'a',
            InputOption::VALUE_REQUIRED,
            'The app name to run server.',
            'main'
        );

        $command->addOption(
            'workers',
            null,
            InputOption::VALUE_REQUIRED,
            'The number of workers.'
        );

        $command->addOption(
            'task-workers',
            null,
            InputOption::VALUE_REQUIRED,
            'The number of task workers.'
        );

        $command->addOption(
            'max-requests',
            null,
            InputOption::VALUE_REQUIRED,
            'The max requests number before reload server.',
            500
        );

        $command->addOption(
            'watch',
            null,
            InputOption::VALUE_NONE,
            'Watch file changes and auto reload.'
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
        $this->io = $io;

        $serverName = $io->getArgument('server');

        [$engineName, $name] = explode(':', $serverName) + ['', ''];

        $this->name = $name;

        $engineName = $engineName ?: $io->getOption('engine');
        $host = $io->getOption('host');

        [$domain, $port] = explode(':', $host) + [null, null];

        $port ??= $this->getUnusedPort($host);

        if ($io->getOption('port')) {
            $port = $io->getOption('port');
        }

        return match ($engineName) {
            'php' => $this->runPhpServer($io, $domain, (int) $port),
            'swoole' => $this->runSwooleServer($io, $domain, (int) $port),
            default => $this->invalidEngine($io, $engineName)
        };
    }

    protected function runPhpServer(IOInterface $io, string $domain, int $port): int
    {
        $name = $this->name ?: $io->getOption('name');

        return $this->createEngine('php', $name, $io)
            ->run(
                $domain,
                $port,
                [
                    'main' => $this->getMainFile($io, 'php', $name),
                    'docroot' => $io->getOption('docroot'),
                ]
            );
    }

    protected function runSwooleServer(IOInterface $io, ?string $domain, int $port): int
    {
        $name = $this->name ?: $io->getOption('name');

        return $this->createEngine('swoole', $name, $io)
            ->run(
                $domain,
                $port,
                [
                    'process_name' => $this->app->getAppName(),
                    'main' => $this->mustGetMainFile($io, 'swoole', $name),
                    'app' => $io->getOption('app'),
                    'workers' => TypeCast::safeInteger($io->getOption('workers')),
                    'task_workers' => TypeCast::safeInteger($io->getOption('task-workers')),
                    'max_requests' => TypeCast::safeInteger($io->getOption('max-requests')),
                    'watch' => $io->getOption('watch'),
                    'verbosity' => $io->getVerbosity()
                ]
            );
    }

    protected function getUnusedPort(string $host, int $start = 8000): string
    {
        while (!$this->portAvailable($host, $start)) {
            $start++;
        }

        return (string) $start;
    }

    protected function portAvailable(string $host, int $port): bool
    {
        try {
            $connection = @fsockopen($host, $port);

            if ($connection) {
                fclose($connection);

                return false;
            }

            return true;
        } catch (Throwable) {
            return true;
        }
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal): void
    {
        $name = $this->name ?: $this->io->getOption('name');
        $engineName = $this->io->getOption('engine');

        $engine = $this->createEngine($engineName, $name, $this->io);

        if ($engine instanceof ServerProcessManageInterface) {
            $engine->stopServer();
        }

        exit(0);
    }
}
