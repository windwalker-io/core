<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LogLevel;
use Windwalker\Core\Application\Offline\MaintenanceManager;
use Windwalker\Core\Console\Process\ProcessRunnerInterface;
use Windwalker\DI\Container;
use Windwalker\Event\EventAwareInterface;

/**
 * Interface ApplicationInterface
 */
interface ApplicationInterface extends
    EventAwareInterface,
    ServiceAwareInterface,
    ProcessRunnerInterface
{
    public const CLIENT_WEB = AppClient::WEB;

    public const CLIENT_CONSOLE = AppClient::CONSOLE;

    public function getAppName(): string;

    /**
     * config
     *
     * @param  string       $name
     * @param  string|null  $delimiter
     *
     * @return  mixed
     */
    public function config(string $name, ?string $delimiter = '.'): mixed;

    /**
     * Get system path.
     *
     * Example: $app->path(`@root/path/to/file`);
     *
     * @param  string  $path
     *
     * @return  string
     */
    public function path(string $path): string;

    /**
     * isDebug
     *
     * @return  bool
     */
    public function isDebug(): bool;

    /**
     * getMode
     *
     * @return  string
     */
    public function getMode(): string;

    /**
     * Method to get property Container
     *
     * @return  Container
     */
    public function getContainer(): Container;

    /**
     * loadConfig
     *
     * @param  mixed        $source
     * @param  string|null  $format
     * @param  array        $options
     *
     * @return  void
     */
    public function loadConfig(mixed $source, ?string $format = null, array $options = []): void;

    public function addMessage(string|array $messages, ?string $type = 'info'): static;

    public function log(string|\Stringable $message, array $context = [], string $level = LogLevel::INFO): static;

    /**
     * Close this request.
     *
     * @param  mixed  $return
     *
     * @return  void
     */
    #[NoReturn]
    public function close(
        mixed $return = ''
    ): void;

    /**
     * Get App client, currently only 'web' and 'console'.
     *
     * @return  AppClient
     */
    public function getClient(): AppClient;

    /**
     * Get client type, will be: web, console and cli_web.
     *
     * If run in Apache, FastCGI, FPM, Nginx, this will be `web`.
     * If run in Swoole, ReactPHP or Amphp, this will be `cli_web`.
     * If run as Windwalker console, this will be `console`.
     *
     * @return  AppType
     */
    public function getType(): AppType;

    /**
     * Is current runtime run in cli?
     *
     * @return  bool
     */
    public function isCliRuntime(): bool;

    /**
     * Get App Secret.
     *
     * @return  string
     */
    public function getSecret(): string;

    /**
     * Is this application under maintenance.
     *
     * @return  bool
     */
    public function isMaintenance(): bool;

    /**
     * Disable the debugger profiler.
     *
     * @param  bool  $disabled
     *
     * @return  void
     */
    public function disableDebugProfiler(bool $disabled = true): void;

    public function isDebugProfilerDisabled(): bool;
}
