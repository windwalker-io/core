<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use JetBrains\PhpStorm\NoReturn;
use Windwalker\DI\Container;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\EventAwareInterface;

/**
 * Interface ApplicationInterface
 */
interface ApplicationInterface extends EventAwareInterface, DispatcherAwareInterface, ServiceAwareInterface
{
    public const CLIENT_WEB = 'web';
    public const CLIENT_CONSOLE = 'console';

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

    /**
     * Close this request.
     *
     * @param  mixed  $return
     *
     * @return  void
     */
    #[NoReturn]
    public function close(mixed $return = ''): void;

    /**
     * Get App client.
     *
     * @return  string
     */
    public function getClient(): string;
}
