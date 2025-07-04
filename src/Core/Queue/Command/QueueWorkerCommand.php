<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue\Command;

use DomainException;
use Laravel\SerializableClosure\SerializableClosure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Manager\Logger;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Event\EventInterface;
use Windwalker\Queue\Event\AfterJobRunEvent;
use Windwalker\Queue\Event\BeforeJobRunEvent;
use Windwalker\Queue\Event\JobFailureEvent;
use Windwalker\Queue\Event\LoopEndEvent;
use Windwalker\Queue\Event\LoopFailureEvent;
use Windwalker\Queue\Event\LoopStartEvent;
use Windwalker\Queue\Failer\QueueFailerInterface;
use Windwalker\Queue\Queue;
use Windwalker\Queue\QueueMessage;
use Windwalker\Queue\Worker;

/**
 * The QueueWorkerCommand class.
 */
#[CommandWrapper(
    description: 'Start a queue worker.'
)]
class QueueWorkerCommand implements CommandInterface
{
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
            'delay',
            'd',
            InputOption::VALUE_REQUIRED,
            'Delay time for failed job to wait next run.',
            '0'
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
            'tries',
            't',
            InputOption::VALUE_REQUIRED,
            'Number of times to attempt a job if it failed.',
            '5'
        );

        $command->addOption(
            'timeout',
            null,
            InputOption::VALUE_REQUIRED,
            'Number of seconds that a job can run.',
            '60'
        );

        $command->addOption(
            'file',
            null,
            InputOption::VALUE_REQUIRED,
            'The job file to run once.',
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

        $channels = $io->getArgument('channels') ?: 'default';
        $options = $this->getWorkOptions($io);
        $connection = $io->getOption('connection') ?: $this->app->config('queue.default');

        $worker = $this->createWorker($connection);
        $worker->setJobRunner($this->runJob(...));

        $worker->addEventDealer($this->app);

        $this->listenToWorker($worker, $io, $connection);

        if ($io->getOption('once')) {
            $file = $io->getOption('file');

            $worker->getEventDispatcher()->on(
                JobFailureEvent::class,
                function (JobFailureEvent $event) {
                    $code = $event->exception->getCode();

                    exit($code === 0 ? 1 : $code);
                }
            );

            if ($file) {
                /** @var QueueMessage $message */
                $message = unserialize(file_get_contents($file));
                $worker->process($message, $options);
            } else {
                $worker->runNextJob($channels, $options);
            }
        } else {
            $worker->loop($channels, $options);
        }

        return 0;
    }

    /**
     * getWorkOptions
     *
     * @return  array
     */
    protected function getWorkOptions(IOInterface $io): array
    {
        return [
            'once' => $io->getOption('once'),
            'delay' => (int) $io->getOption('delay'),
            'force' => $io->getOption('force'),
            'memory' => (int) $io->getOption('memory'),
            'sleep' => (int) $io->getOption('sleep'),
            'tries' => (int) $io->getOption('tries'),
            'timeout' => (int) $io->getOption('timeout'),
            'restart_signal' => $this->app->path('@temp') . '/queue/restart',
        ];
    }

    public function runJob(callable $job): mixed
    {
        return $this->app->call($job);
    }

    protected function listenToWorker(Worker $worker, IOInterface $io, string $connection): void
    {
        $worker->on(
            BeforeJobRunEvent::class,
            function (BeforeJobRunEvent $event) {
                $this->app->addMessage(
                    sprintf(
                        'Run Job: <info>%s</info> - Message ID: <info>%s</info>',
                        get_debug_type($event->job),
                        $event->message->getId()
                    )
                );
            }
        )
            ->on(
                AfterJobRunEvent::class,
                function (AfterJobRunEvent $event) use ($connection, $io, $worker) {
                    $this->app->addMessage(
                        sprintf(
                            'Job Message: <info>%s</info> END',
                            $event->message->getId()
                        )
                    );

                    $this->runEndScripts(
                        'job_end_scripts',
                        $worker,
                        $event,
                        $io,
                        $connection
                    );
                }
            )
            ->on(
                JobFailureEvent::class,
                function (JobFailureEvent $event) use ($io, $connection) {
                    $message = $event->message;
                    $e = $event->exception;

                    Logger::error('queue-error', $e);

                    $this->app->addMessage(
                        sprintf(
                            'Job %s failed - ID: <info>%s</info> - %s',
                            get_debug_type($event->job),
                            $message->getId(),
                            $event->exception->getMessage(),
                        ),
                        'error'
                    );

                    if ($message->isDeleted()) {
                        $this->app->service(QueueFailerInterface::class)
                            ->add(
                                $connection,
                                $message->getChannel(),
                                json_encode($message),
                                (string) $event->exception
                            );
                    }

                    if ($io->getOption('once')) {
                        throw $e;
                    }

                    $this->app->renderThrowable($e, $io->getOutput());
                }
            )
            ->on(
                LoopStartEvent::class,
                function (LoopStartEvent $event) {
                    $worker = $event->worker;

                    switch ($worker->getState()) {
                        case $worker::STATE_ACTIVE:
                            if ($this->app->isMaintenance()) {
                                $worker->setState($worker::STATE_PAUSE);
                            }
                            break;

                        case $worker::STATE_PAUSE:
                            if (!$this->app->isMaintenance()) {
                                $worker->setState($worker::STATE_ACTIVE);
                            }
                            break;
                    }
                }
            )
            ->on(
                LoopFailureEvent::class,
                function (LoopFailureEvent $event) use ($io) {
                    $e = $event->exception;

                    $this->app->addMessage(
                        sprintf(
                            '%s File: %s (%s)',
                            $e->getMessage(),
                            $e->getFile(),
                            $e->getLine()
                        ),
                        'error'
                    );

                    $this->app->renderThrowable($e, $io->getOutput());
                }
            )
            ->on(
                LoopEndEvent::class,
                function (LoopEndEvent $event) use ($connection, $io, $worker) {
                    // Stop connections.
                    $this->runEndScripts('loop_end_scripts', $worker, $event, $io, $connection);
                }
            );
    }

    /**
     * @param  ?string  $connection
     *
     * @return  Worker
     *
     * @throws DefinitionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function createWorker(?string $connection): Worker
    {
        return new Worker(
            queue: $this->app->retrieve(Queue::class, tag: $connection),
            logger: $this->app->retrieve(LoggerInterface::class, tag: 'queue')
        );
    }

    protected function runEndScripts(
        string $configName,
        Worker $worker,
        EventInterface $event,
        IOInterface $io,
        string $connection
    ): void {
        $scripts = $this->app->config('queue.' . $configName) ?? [];

        if (is_callable($scripts)) {
            $scripts = [$scripts];
        }

        foreach ($scripts as $script) {
            $this->app->call(
                $script,
                [
                    'worker' => $worker,
                    Worker::class => $worker,
                    'event' => $event,
                    $event::class => $event,
                    'io' => $io,
                    IOInterface::class => $io,
                    'connection' => $connection,
                ]
            );
        }
    }
}
