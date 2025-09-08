<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue\Command;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Console\IO;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Manager\Logger;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\Exception\DefinitionNotFoundException;
use Windwalker\DI\Exception\DependencyResolutionException;
use Windwalker\Queue\AbstractRunner;
use Windwalker\Queue\Enqueuer;
use Windwalker\Queue\Enqueuer\EnqueuerController;
use Windwalker\Queue\Event\AfterEnqueueEvent;
use Windwalker\Queue\Event\BeforeEnqueueEvent;
use Windwalker\Queue\Event\DebugOutputEvent;
use Windwalker\Queue\Event\EnqueueFailureEvent;
use Windwalker\Queue\Event\LoopEndEvent;
use Windwalker\Queue\Event\LoopFailureEvent;
use Windwalker\Queue\Event\LoopStartEvent;
use Windwalker\Queue\Event\StopEvent;
use Windwalker\Queue\Queue;
use Windwalker\Queue\RunnerOptions;

trait QueueCommandTrait
{
    protected LoggerInterface $logger {
        get => $this->logger ??= $this->createLogger();
    }

    abstract protected function createLogger(): LoggerInterface;

    protected function displayInfo(string $connection, array|string $channels, RunnerOptions $options): void
    {
        $style = $this->io->style();

        $info1[] = "Connection: <info>{$connection}</info>";
        $info1[] = 'Channels: <info>' . (is_array($channels) ? implode(', ', $channels) : $channels) . '</info>';
        $info1[] = 'Memory limit: <info>' . static::unit($options->memoryLimit, 'MB', 'no limit') . '</info>';
        $info1[] = 'Sleep: <info>' . static::unit($options->sleep, 's', 'no sleep') . '</info>';

        $info2[] = 'Timeout: <info>' . static::unit($options->timeout, 's', 'no timeout') . '</info>';
        $info2[] = 'Max runs: <info>' . static::unit($options->maxRuns, '', 'unlimited') . '</info>';
        $info2[] = 'Max lifetime: <info>' . static::unit($options->lifetime, 's', 'unlimited') . '</info>';
        $info2[] = 'Stop when empty: ' . ($options->stopWhenEmpty ? '<info>yes</info>' : '<comment>no</comment>');

        $style->createTable()
            ->setRows(compact('info1', 'info2'))
            ->render();
    }

    protected static function unit(int|float $size, string $unit, string $fallback): string
    {
        return $size ? $size . $unit : $fallback;
    }

    /**
     * @param  IOInterface  $io
     * @param  AbstractRunner  $runner
     *
     * @return  void
     *
     * @throws DefinitionException
     */
    public function prepareDebugServices(IOInterface $io, AbstractRunner $runner): void
    {
        if ($io->isVerbose()) {
            $this->app->container->share(IOInterface::class, $io);
            $this->app->container->share(Output::class, $io->getOutput());
        } else {
            $this->app->container->share(
                IOInterface::class,
                new IO(
                    new ArrayInput([]),
                    new NullOutput(),
                    new Command()
                )
            );
            $this->app->container->share(Output::class, NullOutput::class);
        }

        $runner->on(
            DebugOutputEvent::class,
            function (DebugOutputEvent $event) use ($io) {
                if ($this->canShowLog($event->level)) {
                    $io->writeln(
                        sprintf(
                            '[%s] %s %s',
                            $event->level,
                            $event->message,
                            $event->context ? json_encode($event->context) : ''
                        )
                    );
                }
            }
        );
    }

    protected function getAvailableLogLevels(int $verbosity): array
    {
        $levels = [];

        if ($verbosity >= OutputInterface::VERBOSITY_NORMAL) {
            $levels[] = LogLevel::EMERGENCY;
            $levels[] = LogLevel::ALERT;
            $levels[] = LogLevel::CRITICAL;
            $levels[] = LogLevel::ERROR;
        }

        if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            $levels[] = LogLevel::WARNING;
            $levels[] = LogLevel::NOTICE;
            $levels[] = LogLevel::INFO;
        }

        if ($verbosity >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $levels[] = LogLevel::DEBUG;
        }

        return $levels;
    }

    protected function canShowLog(string $level, ?int $verbosity = null): bool
    {
        $verbosity ??= $this->io->getVerbosity();

        return in_array($level, $this->getAvailableLogLevels($verbosity), true);
    }
}
