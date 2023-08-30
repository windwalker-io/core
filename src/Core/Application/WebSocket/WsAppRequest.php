<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application\WebSocket;

use Windwalker\Core\Application\Context\AppRequestTrait;
use Windwalker\Core\Event\CoreEventAwareTrait;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Filter\Traits\FilterAwareTrait;
use Windwalker\Reactor\WebSocket\WebSocketFrameInterface;
use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;

/**
 * The WebSocketAppRequest class.
 */
class WsAppRequest implements \JsonSerializable, EventAwareInterface, WebSocketFrameInterface
{
    use CoreEventAwareTrait;
    use FilterAwareTrait;
    use AppRequestTrait;

    /**
     * AppRequest constructor.
     *
     * @param  WebSocketRequestInterface  $request
     * @param  SystemUri                  $systemUri
     * @param  ProxyResolver              $proxyResolver
     */
    public function __construct(
        protected WebSocketRequestInterface $request,
        protected SystemUri $systemUri,
        protected ProxyResolver $proxyResolver
    ) {
        //
    }

    public function getFd(): int
    {
        return $this->request->getFd();
    }

    public function getData(): string
    {
        return $this->request->getData();
    }

    protected function compileInput(): mixed
    {
        return $this->input = $this->request->getParsedData();
    }

    protected function getUriQueryValues(): array
    {
        return $this->getUri()->getQueryValues();
    }

    public function getUrlVars(): array
    {
        if (!$this->matchedRoute) {
            return [];
        }

        return $this->matchedRoute->getVars();
    }
}
