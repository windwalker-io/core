<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue\Command;

use InvalidArgumentException;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Queue\QueueManager;
use Windwalker\Queue\Failer\QueueFailerInterface;
use Windwalker\Queue\Queue;

/**
 * The QueueRestartCommand class.
 */
#[CommandWrapper(
    description: 'Retry failed jobs.'
)]
class QueueRetryCommand implements CommandInterface
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
            'ids',
            InputArgument::IS_ARRAY,
            'Job IDs.'
        );
        $command->addOption(
            'delay',
            'd',
            InputOption::VALUE_REQUIRED,
            'Delay time for failed job to wait next run.',
            '0'
        );
        $command->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'Retry all failed jobs.',
        );
        $command->addOption(
            'failer',
            null,
            InputOption::VALUE_REQUIRED,
            'Failer connection.',
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function execute(IOInterface $io): int
    {
        $failerName = $io->getOption('failer');
        $failer = $this->app->retrieve(QueueFailerInterface::class, tag: $failerName);

        $all = $io->getOption('all');
        $delay = $io->getOption('delay');

        if ($all) {
            $ids = (function () use ($failer) {
                foreach ($failer->all() as $item) {
                    yield $item['id'];
                }
            })();
        } else {
            $ids = $io->getArgument('ids');

            if ($ids === []) {
                throw new InvalidArgumentException(
                    'Please provide at least 1 ID or use --all option.'
                );
            }
        }

        foreach ($ids as $id) {
            $failed = $failer->get($id);

            if ($failed === null) {
                continue;
            }

            $connection = $failed['connection'] ?? null;

            $queue = $this->app->retrieve(Queue::class, tag: $connection);

            $queue->pushRaw(json_decode($failed['body'], true), (int) $delay, $failed['channel']);

            if (!$all) {
                $io->writeln(
                    sprintf(
                        'Resend failed-job: <info>%s</info> to connection: <info>%s</info> ' .
                        'queue: <comment>%s</comment>',
                        $id,
                        $connection,
                        $failed['channel']
                    )
                );

                $failer->remove($id);
            }
        }

        if ($all) {
            $io->writeln('Flush all failed-jobs');

            $failer->clear();
        }

        return 0;
    }
}
