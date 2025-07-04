<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use Closure;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Windwalker\Core\Manager\AbstractManager;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Queue\Job\JobController;
use Windwalker\Queue\Queue;
use Windwalker\Queue\QueueMessage;

/**
 * The QueueManager class.
 *
 * @method Queue get(?string $name = null, ...$args)
 *
 * @deprecated  Use container tags instead.
 */
#[Isolation]
class QueueManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'queue';
    }

    public function create(?string $name = null, ...$args): object
    {
        $instance = parent::create($name, ...$args);

        $instance->getObjectBuilder()->setBuilder(
            function (mixed $class, ...$args) {
                return $this->container->resolve($class, $args);
            }
        );

        return $instance;
    }

    public function createQueue(string $driverClass, ...$args): Queue
    {
        return new Queue(
            $this->container->newInstance(
                $driverClass,
                $args
            )
        );
    }

    public static function createSyncHandler(): Closure
    {
        return static function (QueueMessage $message) {
            $tmp = Filesystem::createTemp(WINDWALKER_TEMP);
            $tmp->write(serialize($message));

            register_shutdown_function(static fn() => $tmp->delete());

            $process = new Process(
                [
                    new PhpExecutableFinder()->find(),
                    'windwalker',
                    'queue:worker',
                    '--once',
                    '--file=' . $tmp . '',
                ]
            )
                ->setWorkingDirectory(WINDWALKER_ROOT)
                ->mustRun();

            return $process->getOutput();
        };
    }

    public function createContainerHandler(): Closure
    {
        return fn(QueueMessage $message) => $message->run(
            fn(JobController $controller, callable $invokable) => $this->container->call(
                $invokable,
                [
                    JobController::class => $controller,
                    'jobController' => $controller,
                    'controller' => $controller,
                ]
            )
        );
    }
}
