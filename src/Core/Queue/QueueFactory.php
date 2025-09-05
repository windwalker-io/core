<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use Closure;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Windwalker\Core\DI\ServiceFactoryInterface;
use Windwalker\Core\DI\ServiceFactoryTrait;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Attributes\Factory;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\DI\Container;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Queue\Driver\DatabaseQueueDriver;
use Windwalker\Queue\Driver\SqsQueueDriver;
use Windwalker\Queue\Driver\SyncQueueDriver;
use Windwalker\Queue\Enum\DatabaseIdType;
use Windwalker\Queue\Job\JobController;
use Windwalker\Queue\Queue;
use Windwalker\Queue\QueueMessage;

/**
 * The QueueManager class.
 *
 * @method Queue get(?string $name = null, ...$args)
 */
#[Isolation]
class QueueFactory implements ServiceFactoryInterface
{
    use ServiceFactoryTrait {
        create as protected parentCreate;
    }

    public function getClassName(): ?string
    {
        return Queue::class;
    }

    public function getConfigPrefix(): string
    {
        return 'queue';
    }

    public function create(?string $name = null, ...$args): object
    {
        /** @var Queue $instance */
        $instance = $this->parentCreate($name, ...$args);

        $instance->getObjectBuilder()->setBuilder(
            function (mixed $class, ...$args) {
                return $this->container->resolve($class, $args);
            }
        );

        return $instance;
    }

    public static function syncAdapter(callable $handler, array $options = []): \Closure
    {
        return #[Factory]
        function (Container $container) use ($handler, $options) {
            return new Queue(
                $container->newInstance(
                    SyncQueueDriver::class,
                    compact('handler', 'options')
                )
            );
        };
    }

    public static function databaseAdapter(
        DatabaseAdapter $db,
        string $channel,
        string $table,
        int $timeout = 60,
        DatabaseIdType $idType = DatabaseIdType::INT
    ): \Closure {
        return #[Factory]
        function (Container $container) use ($db, $channel, $table, $timeout, $idType) {
            return new Queue(
                $container->newInstance(
                    DatabaseQueueDriver::class,
                    compact('db', 'channel', 'table', 'timeout', 'idType')
                )
            );
        };
    }

    public static function sqsAdapter(
        string $key,
        string $secret,
        string $channel = 'default',
        array $options = []
    ): \Closure {
        return #[Factory]
        function (Container $container) use ($key, $secret, $channel, $options) {
            return new Queue(
                $container->newInstance(
                    SqsQueueDriver::class,
                    compact('key', 'secret', 'channel', 'options')
                )
            );
        };
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
