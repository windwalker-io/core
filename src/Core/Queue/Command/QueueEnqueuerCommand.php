<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue\Command;

use DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Queue\Event\EnqueueFailureEvent;
use Windwalker\Queue\RunnerOptions;
use Windwalker\Queue\Worker;

/**
 * The QueueWorkerCommand class.
 */
#[CommandWrapper(
    description: 'Start an enqueuer.'
)]
class QueueEnqueuerCommand implements CommandInterface
{
    use QueueCommandTrait;
    use EnqueuerCommandTrait;

    protected IOInterface $io;

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
            'channels',
            InputArgument::IS_ARRAY,
            'The channel name to run.'
        );

        $command->addOption(
            'connection',
            'c',
            InputOption::VALUE_REQUIRED,
            'The connection of queue.'
        );

        $command->addOption(
            'once',
            'o',
            InputOption::VALUE_NONE,
            'Only run next job.'
        );

        $command->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force run worker if in pause mode.'
        );

        $command->addOption(
            'memory',
            'm',
            InputOption::VALUE_REQUIRED,
            'The memory limit in megabytes.',
            '128'
        );

        $command->addOption(
            'sleep',
            's',
            InputOption::VALUE_REQUIRED,
            'Number of seconds to sleep after job run complete.',
            '1'
        );

        $command->addOption(
            'timeout',
            null,
            InputOption::VALUE_REQUIRED,
            'Number of seconds that a job can run.',
            '60'
        );

        $command->addOption(
            'max-runs',
            null,
            InputOption::VALUE_REQUIRED,
            'The max times to run the worker before exit. 0 for unlimited.',
            '0'
        );

        $command->addOption(
            'lifetime',
            null,
            InputOption::VALUE_REQUIRED,
            'The max seconds to run the worker before exit. 0 for unlimited.',
            '0'
        );

        $command->addOption(
            'stop-when-empty',
            'e',
            InputOption::VALUE_NONE,
            'Stop the worker when no job is remaining.',
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     * @throws DefinitionException
     */
    public function execute(IOInterface $io): int
    {
        if (!class_exists(Worker::class)) {
            throw new DomainException('Please install windwalker/queue first.');
        }

        $this->io = $io;

        $channels = $io->getArgument('channels') ?: 'default';
        $options = $this->getRunnerOptions($io);
        $connection = $io->getOption('connection') ?: $this->app->config('queue.default');

        $enqueuer = $this->createEnqueuer($connection, $options);
        $this->prepareEnqueuer($enqueuer);

        $this->prepareDebugServices($io, $enqueuer);

        $enqueuer->addEventDealer($this->app);

        $this->listenToEnqueuer($enqueuer, $io, $connection);

        // Show enqueuer start information
        $io->writeln(
            sprintf(
                "Enqueuer started. Press <info>Ctrl+C</info> to stop. PID: <comment>%s</comment>",
                getmypid()
            )
        );
        $this->displayInfo($connection, $channels, $options);

        if ($io->getOption('once')) {
            $enqueuer->getEventDispatcher()->on(
                EnqueueFailureEvent::class,
                function (EnqueueFailureEvent $event) {
                    $code = $event->exception->getCode();

                    exit($code === 0 ? 1 : $code);
                }
            );

            $enqueuer->next($channels);
        } else {
            $enqueuer->loop($channels);
        }

        return 0;
    }

    protected function getRunnerOptions(IOInterface $io): RunnerOptions
    {
        return new RunnerOptions(
            once: (bool) $io->getOption('once'),
            // backoff: (int) ($io->getOption('backoff') ?? $io->getOption('delay')),
            force: (bool) $io->getOption('force'),
            memoryLimit: (int) $io->getOption('memory'),
            sleep: (float) $io->getOption('sleep'),
            // tries: (int) $io->getOption('tries'),
            timeout: (int) $io->getOption('timeout'),
            maxRuns: (int) $io->getOption('max-runs'),
            lifetime: (int) $io->getOption('lifetime'),
            stopWhenEmpty: (bool) $io->getOption('stop-when-empty'),
            restartSignal: $this->app->path('@temp') . '/queue/enqueuer-restart',
        );
    }

    protected function createLogger(): LoggerInterface
    {
        return $this->app->retrieve(LoggerInterface::class, tag: 'system/enqueuer');
    }
}
