<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Console;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Whoops\Exception\Frame;
use Whoops\Exception\Inspector;
use Whoops\Handler\CallbackHandler;
use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Console\Command\RootCommand;
use Windwalker\Console\Console;
use Windwalker\Console\IO\IOFactory;
use Windwalker\Console\IO\IOInterface;
use Windwalker\Console\IO\NullInput;
use Windwalker\Core;
use Windwalker\Core\Config\Config;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Utilities\Classes\BootableTrait;
use Windwalker\Core\Utilities\Debug\BacktraceHelper;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\Debugger\Helper\ComposerInformation;
use Windwalker\DI\Container;
use Windwalker\Environment\Environment;
use Windwalker\Environment\PlatformHelper;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\DispatcherInterface;
use Windwalker\Event\EventInterface;
use Windwalker\Language\Language;
use Windwalker\Session\Session;
use Windwalker\Structure\Structure;

/**
 * The Console class.
 *
 * @property-read  Container                     container
 * @property-read  Core\Logger\LoggerManager     logger
 * @property-read  Structure                     config
 * @property-read  Core\Event\EventDispatcher    dispatcher
 * @property-read  AbstractDatabaseDriver        database
 * @property-read  Core\Router\MainRouter        router
 * @property-read  Language                      language
 * @property-read  Core\Renderer\RendererManager renderer
 * @property-read  Core\Cache\CacheFactory       cache
 * @property-read  Session                       session
 * @property-read  Environment                   environment
 * @property-read  Core\Queue\Queue              queue
 *
 * @since  2.0
 */
class CoreConsole extends Console implements Core\Application\WindwalkerApplicationInterface, DispatcherAwareInterface
{
    use BootableTrait;
    use Core\WindwalkerTrait;

    /**
     * The Console name.
     *
     * @var  string
     *
     * @since  2.0
     */
    protected $name = 'console';

    /**
     * The Console title.
     *
     * @var  string
     */
    protected $title = 'Windwalker Console';

    /**
     * Version of this application.
     *
     * @var string
     */
    protected $version = '3';

    /**
     * The DI container.
     *
     * @var Container
     */
    protected $container;

    /**
     * Property config.
     *
     * @var Structure
     */
    protected $config;

    /**
     * Property mode.
     *
     * @var  string
     */
    protected $mode;

    /**
     * Property autoExit.
     *
     * @var  bool
     */
    protected $autoExit = true;

    /**
     * Class init.
     *
     * @param   IOInterface $io     The Input and output handler.
     * @param   Config      $config Application's config object.
     */
    public function __construct(IOInterface $io = null, Config $config = null)
    {
        $this->config = $config instanceof Config ? $config : new Config();
        $this->name   = $this->config->get('name', $this->name);

        Core\Ioc::setProfile($this->name);

        $this->container = Core\Ioc::factory();

        parent::__construct($io, $this->config);
    }

    /**
     * initialise
     *
     * @return  void
     */
    protected function init()
    {
        //
    }

    /**
     * Method to set property name
     *
     * @param   string $name
     *
     * @return  static  Return self to support chaining.
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * registerCommands
     *
     * @return  void
     */
    public function registerCommands()
    {
        $commands = (array) $this->get('console.commands');

        $reader = $this->container->getAnnotationRegistry()->getAnnotationReader();

        foreach ($commands as $name => $command) {
            if ($command === false) {
                continue;
            }

            if (is_string($command) && is_file($command)) {
                $command = include $command;
            }

            if (is_callable($command)) {
                $parentCommand = $this->getRootCommand();

                if (str_contains($name, '/')) {
                    $path = explode('/', $name);

                    $name = array_pop($path);

                    $parentCommand = $this->registerCommandRecursively($path);
                }

                $cmd = $command;

                // Workaround for Container not suppports object::__invoke() now.
                if (is_object($cmd) && !$cmd instanceof \Closure) {
                    /** @var CommandMeta $meta */
                    $meta = $reader->getClassAnnotation(new \ReflectionClass($cmd), CommandMeta::class);

                    $cmd = [$cmd, '__invoke'];
                } elseif (is_array($cmd)) {
                    $meta = $reader->getMethodAnnotation(new \ReflectionMethod($cmd[0], $cmd[1]), CommandMeta::class);
                }

                $command = function (...$args) use ($cmd) {
                    return $this->container->call($cmd, $args);
                };

                $command = $parentCommand->addCommand($name, null, [], $command);

                if (isset($meta)) {
                    $meta($this, $command);
                }

                continue;
            }

            if (is_string($command)) {
                $command = $this->container->createObject($command);
            }

            $this->addCommand($command);
        }
    }

    /**
     * getAndRegisterCommandRecursively
     *
     * @param array $paths
     *
     * @return  AbstractCommand
     *
     * @since  3.5.22.3
     */
    protected function registerCommandRecursively(array $paths): AbstractCommand
    {
        $parent = $this->getRootCommand();

        foreach ($paths as $path) {
            $child = $parent->getChild($path);

            if (!$child) {
                $child = $parent->addCommand($path);
            }

            $parent = $child;
        }

        return $parent;
    }

    /**
     * Execute the application.
     *
     * @return void Exit with the Unix Console/Shell exit code.
     *
     * @throws \Exception
     * @since   2.0
     */
    public function execute()
    {
        $this->boot();

        $this->registerRootCommand();

        $this->registerCommands();

        $this->prepareExecute();

        $this->triggerEvent('onBeforeExecute', ['app' => $this]);

        // Perform application routines.
        $exitCode = $this->doExecute();

        $this->triggerEvent('onAfterExecute', ['app' => $this]);

        exit($this->postExecute($exitCode));
    }

    /**
     * Register default command.
     *
     * @return  static  Return this object to support chaining.
     *
     * @since  2.0
     */
    public function registerRootCommand()
    {
        if ($this->rootCommand) {
            return $this;
        }

        $this->rootCommand = $this->make(RootCommand::class, ['io' => $this->io]);

        $this->rootCommand->setApplication($this);

        $this->description ? $this->rootCommand->description($this->description) : null;
        $this->help ? $this->rootCommand->help($this->help) : null;

        $this->getRootCommand()
            ->addGlobalOption('n')
            ->alias('no-interactive')
            ->defaultValue(false)
            ->description('Ignore interactions and assume to default value.');

        return $this;
    }

    /**
     * Prepare execute hook.
     *
     * @return  void
     */
    protected function prepareExecute()
    {
        ini_set('max_execution_time', '0');

        if (class_exists(ComposerInformation::class)) {
            $this->version = ComposerInformation::getInstalledVersion('windwalker/core');
        }

        if ($this->getRootCommand()->getOption('n')) {
            IOFactory::getIO()->setInput(new NullInput());
        }
    }

    /**
     * Trigger an event.
     *
     * @param   EventInterface|string $event The event object or name.
     * @param   array                 $args  The arguments.
     *
     * @return  EventInterface  The event after being passed through all listeners.
     *
     * @since   2.0
     */
    public function triggerEvent($event, $args = [])
    {
        /** @var \Windwalker\Event\Dispatcher $dispatcher */
        $dispatcher = $this->container->get('dispatcher');

        $dispatcher->triggerEvent($event, $args);

        return $event;
    }

    /**
     * getPackage
     *
     * @param string $name
     *
     * @return  AbstractPackage
     */
    public function getPackage($name = null)
    {
        $key = 'package.' . strtolower($name);

        if ($this->container->exists($key)) {
            return $this->container->get($key);
        }

        return null;
    }

    /**
     * createProcess
     *
     * @param string $script
     *
     * @return  Process
     *
     * @since  3.5.22
     */
    public function createProcess(string $script): Process
    {
        if (!class_exists(Process::class)) {
            throw new \DomainException('Please install symfony/process first.');
        }

        $process = Process::fromShellCommandline($script);
        $process->setTimeout(0);

        $phpPath = dirname((new PhpExecutableFinder())->find());

        $path = implode(
            PlatformHelper::isWindows() ? ';' : ':',
            [
                $phpPath,
                WINDWALKER_ROOT . '/vendor/bin',
                WINDWALKER_ROOT . '/bin',
                env('PATH') ?? env('Path')
            ]
        );

        $env = $process->getEnv();
        $env['PATH'] = $path;

        if (PlatformHelper::isWindows()) {
            $env['Path'] = $path;
        }

        $process->setEnv($env);

        $process->setWorkingDirectory(WINDWALKER_ROOT);

        return $process;
    }

    /**
     * processOutputCallback
     *
     * @param string $type
     * @param string $buffer
     *
     * @return  void
     *
     * @since  3.5.22
     */
    public function processOutputCallback($type, $buffer): void
    {
        if (Process::ERR === $type) {
            $this->io->err($buffer, false);
        } else {
            $this->io->out($buffer, false);
        }
    }

    /**
     * runProcess
     *
     * @param string      $script
     * @param string|null $input
     *
     * @return  int
     *
     * @since  3.5.5
     */
    public function runProcess(string $script, ?string $input = null): int
    {
        $this->out()->out();
        $this->addMessage('>>> ' . $script, 'info');

        if (class_exists(Process::class)) {
            $process = $this->createProcess($script);

            if ($input !== null) {
                $process->setInput($input);
            }

            return $process->run([$this, 'processOutputCallback']);
        }

        system($script, $return);

        return (int) $return;
    }

    /**
     * handleException
     *
     * @param \Throwable $exception
     *
     * @return  void
     *
     * @since  3.5.2
     * @throws \Exception
     */
    public function handleException(\Throwable $exception): void
    {
        if (!$this->get('error.log', false)) {
            return;
        }

        // Do not log 4xx errors
        $code = $exception->getCode();

        if ($code < 400 || $code >= 500) {
            $verbose = $this->get('verbose', 0);

            Core\Logger\Logger::error('console-error', (string) $exception);

            if (!$verbose) {
                $this->out()->err($exception->getMessage());

                return;
            }

            $handler = new CallbackHandler(function (\Throwable $e, Inspector $inspector) {
                /** @var $exception \Exception */
                $class = $inspector->getExceptionName();

                $trace = [];

                /** @var Frame $frame */
                foreach ($inspector->getFrames() as $i => $frame) {
                    $trace[] = BacktraceHelper::traceAsString($i + 1, $frame->getRawFrame(), false);
                }

                $trace = implode("\n", $trace);

                // @codingStandardsIgnoreStart
                $output = <<<EOF
<error>Exception '{$class}' with message:</error> <fg=cyan;options=bold>{$inspector->getExceptionMessage()}</fg=cyan;options=bold>
<info>in {$inspector->getException()->getFile()}:{$inspector->getException()->getLine()}</info>

<error>Stack trace:</error>
{$trace}
EOF;
                // @codingStandardsIgnoreEnd

                $this->out('');
                $this->err($output);
            });

            $handler->setException($exception);
            $handler->setInspector(new Inspector($exception));
            $handler->handle();
        }
    }

    /**
     * addPackage
     *
     * @param string          $name
     * @param AbstractPackage $package
     *
     * @return  static
     */
    public function addPackage($name, AbstractPackage $package)
    {
        $this->container->get('package.resolver')->addPackage($name, $package);

        return $this;
    }

    /**
     * Method to set property container
     *
     * @param   Container $container
     *
     * @return  static  Return self to support chaining.
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Method to get property Container
     *
     * @return  Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * getDispatcher
     *
     * @return  DispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->container->get('dispatcher');
    }

    /**
     * setDispatcher
     *
     * @param   DispatcherInterface $dispatcher
     *
     * @return  static  Return self to support chaining.
     */
    public function setDispatcher(DispatcherInterface $dispatcher)
    {
        $this->container->share('dispatcher', $dispatcher);

        return $this;
    }

    /**
     * addMessage
     *
     * @param string|array $messages
     * @param string       $type
     *
     * @return  static
     */
    public function addMessage($messages, $type = null)
    {
        switch ($type) {
            case 'success':
            case 'green':
                $tag = '<info>%s</info>';
                break;

            case 'warning':
            case 'yellow':
                $tag = '<comment>%s</comment>';
                break;

            case 'info':
            case 'blue':
                $tag = '<option>%s</option>';
                break;

            case 'error':
            case 'danger':
            case 'red':
                $tag = '<error>%s</error>';
                break;

            default:
                $tag = '%s';
                break;
        }

        foreach ((array) $messages as $message) {
            $time = gmdate('Y-m-d H:i:s');

            $this->out(sprintf('[%s] ' . $tag, $time, $message));
        }

        return $this;
    }

    /**
     * Method to get property Mode
     *
     * @return  string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * is utilized for reading data from inaccessible members.
     *
     * @param   $name  string
     *
     * @return  mixed
     * @throws \OutOfRangeException
     */
    public function __get($name)
    {
        $diMapping = [
            'io' => 'io',
            'dispatcher' => 'dispatcher',
            'database' => 'database',
            'language' => 'language',
            'cache' => 'cache',
            'environment' => 'environment',
            'queue' => 'queue',
        ];

        if (isset($diMapping[$name])) {
            return $this->container->get($diMapping[$name]);
        }

        $allowNames = [
            'container',
            'config',
        ];

        if (in_array($name, $allowNames)) {
            return $this->$name;
        }

        throw new \OutOfRangeException(sprintf('property "%s" not found in %s', $name, get_called_class()));
    }
}
