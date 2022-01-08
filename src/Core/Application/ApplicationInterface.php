<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\NoReturn;
use Windwalker\Core\Console\Process\ProcessRunnerInterface;
use Windwalker\DI\Container;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Session\Session;

/**
 * Interface ApplicationInterface
 */
interface ApplicationInterface extends
    EventAwareInterface,
    ServiceAwareInterface,
    ProcessRunnerInterface
{
    public const CLIENT_WEB = 'web';

    public const CLIENT_CONSOLE = 'console';

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
     * Get App client.
     *
     * @return  string
     */
    public function getClient(): string;
}
