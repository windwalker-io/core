<?php

/**
 * Part of datavideo project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Console;

use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Application\WebApplicationInterface;
use Windwalker\Core\Application\WebApplicationTrait;
use Windwalker\DI\Container;
use Windwalker\Http\Response\RedirectResponse;

/**
 * The WebSimulator class.
 */
class WebAppSimulator implements WebApplicationInterface
{
    use WebApplicationTrait;

    /**
     * WebSimulator constructor.
     *
     * @param  Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function close(mixed $return = ''): void
    {
        die(0);
    }

    public function redirect(\Stringable|string $url, int $code = 303, bool $instant = false): ResponseInterface
    {
        return new RedirectResponse($url, $code);
    }

    public function addMessage(array|string $messages, ?string $type = 'info'): static
    {
        return $this;
    }
}
