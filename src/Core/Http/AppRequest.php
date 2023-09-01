<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Http;

use JetBrains\PhpStorm\Immutable;
use JsonSerializable;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\Core\Application\Context\AppRequestTrait;
use Windwalker\Core\Event\CoreEventAwareTrait;
use Windwalker\Core\Http\Event\RequestGetValueEvent;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Data\Collection;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Filter\Traits\FilterAwareTrait;

use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;

use function Windwalker\collect;

/**
 * The AppRequest class.
 */
#[Immutable(Immutable::PRIVATE_WRITE_SCOPE)]
class AppRequest implements AppRequestInterface, JsonSerializable
{
    use CoreEventAwareTrait;
    use FilterAwareTrait;
    use AppRequestTrait;

    /**
     * AppRequest constructor.
     *
     * @param  ServerRequestInterface  $request
     * @param  SystemUri               $systemUri
     * @param  ProxyResolver           $proxyResolver
     */
    public function __construct(
        protected ServerRequestInterface $request,
        protected SystemUri $systemUri,
        protected ProxyResolver $proxyResolver
    ) {
        //
    }

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function getOverrideMethod(): string
    {
        return $this->request->getHeaderLine('X-Http-Method-Override')
            ?: $this->getServerRequest()->getParsedBody()['_method']
            ?? $this->getUri()->getQueryValues()['_method']
            ?? $this->request->getMethod();
    }

    /**
     * @param  ServerRequestInterface  $request
     *
     * @return  static  Return self to support chaining.
     */
    public function withRequest(ServerRequestInterface $request): static
    {
        $new = clone $this;
        $new->request = $request;
        $this->input = null;

        return $new;
    }

    /**
     * inputWithMethod
     *
     * @param  string  $method
     * @param  mixed   ...$fields
     *
     * @return  mixed|Collection
     */
    public function inputWithMethod(string $method = 'REQUEST', ...$fields): mixed
    {
        if ($method === '' || strtoupper($method) === 'REQUEST') {
            $input = $this->compileInput();
        } elseif (strtoupper($method) === 'GET') {
            $input = $this->getQueryValues();
        } else {
            $input = $this->getBodyValues();
        }

        if ($fields === []) {
            return $input;
        }

        return $this->fetchInputFields($input, $fields);
    }

    /**
     * file
     *
     * @param  mixed  ...$fields
     *
     * @return  UploadedFileInterface[]|UploadedFileInterface|array
     */
    public function file(...$fields): mixed
    {
        $files = $this->getRequest()->getUploadedFiles();

        if ($fields === []) {
            return $files;
        }

        $data = [];

        foreach ($fields as $field) {
            $data[$field] = $files[$field] ?? null;
        }

        if (\Windwalker\count($fields) === 1) {
            return array_shift($data);
        }

        return collect($data);
    }

    protected function compileInput(): mixed
    {
        return $this->input ??= array_merge(
            $this->getQueryValues(),
            $this->getBodyValues()
        );
    }

    protected function getUriQueryValues(): array
    {
        $values = $this->getUri()->getQueryValues();

        $appRequest = $this;
        $type = RequestGetValueEvent::TYPE_QUERY;

        $event = $this->emit(
            RequestGetValueEvent::class,
            compact('appRequest', 'values', 'type')
        );

        return $event->getValues();
    }

    public function getUrlVars(): array
    {
        if (!$this->matchedRoute) {
            return [];
        }

        $appRequest = $this;
        $values = $this->matchedRoute->getVars();
        $type = RequestGetValueEvent::TYPE_URL_VARS;

        $event = $this->emit(
            RequestGetValueEvent::class,
            compact('appRequest', 'values', 'type')
        );

        return $event->getValues();
    }

    public function getBodyValues(): array
    {
        $values = $this->getRequest()->getParsedBody();

        $appRequest = $this;
        $type = RequestGetValueEvent::TYPE_BODY;

        $event = $this->emit(
            RequestGetValueEvent::class,
            compact('appRequest', 'values', 'type')
        );

        return $event->getValues();
    }

    public function isAccept(string $type): bool
    {
        return str_contains(
            $this->getRequest()->getHeaderLine('accept'),
            $type
        );
    }

    public function isAcceptJson(): bool
    {
        return $this->isAccept('application/json');
    }
}
