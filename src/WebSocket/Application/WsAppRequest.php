<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Application;

use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\Core\Application\Context\AppRequestTrait;
use Windwalker\Core\Event\CoreEventAwareTrait;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Filter\Traits\FilterAwareTrait;
use Windwalker\Reactor\WebSocket\WebSocketFrameInterface;
use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;

/**
 * The WebSocketAppRequest class.
 */
class WsAppRequest implements AppRequestInterface, \JsonSerializable, WebSocketFrameInterface
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
        return $this->input ??= $this->request->getParsedData();
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

    /**
     * @param  WebSocketRequestInterface  $request
     *
     * @return  static  Return self to support chaining.
     */
    public function withServerRequest(WebSocketRequestInterface $request): static
    {
        $new = clone $this;
        $new->request = $request;
        $this->input = null;

        return $new;
    }

    public function getServerRequest(): WebSocketRequestInterface
    {
        return $this->request;
    }
}
