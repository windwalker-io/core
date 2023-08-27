<?php

/**
 * Part of starter project.
 *
 * @copyright      Copyright (C) 2021 LYRASOFT.
 * @license        MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\CliServer\CliServerEngineFactory;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\DI\Attributes\Autowire;

/**
 * The ServeCommand class.
 */
#[CommandWrapper(
    description: 'Start Windwalker Server.',
    aliases: ['dev:serve']
)]
class ServeCommand implements CommandInterface
{
    /**
     * ServeCommand constructor.
     */
    public function __construct(
        protected ConsoleApplication $app,
        #[Autowire]
        protected CliServerEngineFactory $cliServerFactory
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
            'host',
            InputArgument::OPTIONAL,
            'The server host.',
            'localhost'
        );

        $command->addArgument(
            'main',
            InputArgument::OPTIONAL,
            'The server main file.',
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
            'works',
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
            'max-request',
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
        $engineName = $io->getOption('engine');
        $host = $io->getArgument('host');

        [$domain, $port] = explode(':', $host) + [null, null];

        $port ??= $this->getUnusedPort($host);

        if ($io->getOption('port')) {
            $port = $io->getOption('port');
        }

        return match ($engineName) {
            'php' => $this->runPhpServer($io, $domain, (int) $port),
            // 'swoole' => $this->runPhpServer($io, $domain, (int) $port),
            default => $this->invalidEngine($io, $engineName)
        };
    }

    protected function runPhpServer(IOInterface $io, string $domain, int $port): int
    {
        return $this->cliServerFactory->create('php')
            ->run(
                $domain,
                $port,
                [
                    'main' => $io->getArgument('main'),
                    'docroot' => $io->getOption('docroot'),
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
        } catch (Throwable $e) {
            return true;
        }
    }

    protected function invalidEngine(IOInterface $io, mixed $engineName): int
    {
        $io->errorStyle()->warning('Invalid server engine: ' . $engineName);

        return Command::FAILURE;
    }
}
