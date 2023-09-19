<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\WebSocket\Application;

use JetBrains\PhpStorm\NoReturn;
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Core\Application\Context\AppContextTrait;
use Windwalker\Core\Application\WebRootApplicationInterface;
use Windwalker\DI\Container;
use Windwalker\Reactor\WebSocket\WebSocketFrameInterface;

/**
 * The WsAppContext class.
 *
 * @method WebRootApplicationInterface getRootApp()
 */
class WsAppContext implements WsApplicationInterface, AppContextInterface, WebSocketFrameInterface
{
    use AppContextTrait;
    use WsApplicationTrait;

    protected WsAppRequest $appRequest;

    /**
     * Context constructor.
     *
     * @param  Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getFd(): int
    {
        return $this->getAppRequest()->getFd();
    }

    public function getData(): string
    {
        return $this->getAppRequest()->getData();
    }

    public function pushSelf(mixed ...$args): bool
    {
        return $this->pushTo($this->getFd(), ...$args);
    }

    public function pushRawSelf(string $data): bool
    {
        return $this->pushRawTo($this->getFd(), $data);
    }

    /**
     * @return WsAppRequest
     */
    public function getAppRequest(): WsAppRequest
    {
        return $this->appRequest;
    }

    /**
     * @param  WsAppRequest  $request
     *
     * @return  static  Return self to support chaining.
     */
    public function setAppRequest(WsAppRequest $request): static
    {
        $this->appRequest = $request;

        $request->addEventDealer($this);

        return $this;
    }

    public function addMessage(array|string $messages, ?string $type = 'info'): static
    {
        return $this;
    }

    #[NoReturn] public function close(mixed $return = ''): void
    {
        exit(0);
    }
}
