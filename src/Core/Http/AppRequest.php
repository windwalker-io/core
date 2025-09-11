<?php

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
use Windwalker\Filter\Traits\FilterAwareTrait;

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
     * @param  RequestInspector        $requestInspector
     */
    public function __construct(
        protected ServerRequestInterface $request,
        SystemUri $systemUri,
        ProxyResolver $proxyResolver,
        protected RequestInspector $requestInspector
    ) {
        $this->systemUri = $systemUri;
        $this->proxyResolver = $proxyResolver;
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
        $new->input = null;

        return $new;
    }

    /**
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
        $files = $this->getServerRequest()->getUploadedFiles();

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
        $event = $this->emit(
            new RequestGetValueEvent(
                appRequest: $this,
                type: RequestGetValueEvent::TYPE_QUERY,
                values: $this->getUri()->getQueryValues()
            )
        );

        return $event->values;
    }

    public function getUrlVars(): array
    {
        if (!$this->matchedRoute) {
            return [];
        }

        $event = $this->emit(
            new RequestGetValueEvent(
                appRequest: $this,
                type: RequestGetValueEvent::TYPE_URL_VARS,
                values: $this->matchedRoute->getVars()
            )
        );

        return $event->values;
    }

    public function getBodyValues(): array
    {
        $event = $this->emit(
            new RequestGetValueEvent(
                appRequest: $this,
                type: RequestGetValueEvent::TYPE_BODY,
                values: $this->getServerRequest()->getParsedBody()
            )
        );

        return $event->values;
    }

    public function isAccept(string $type): bool
    {
        return $this->requestInspector->isAccept($this->getServerRequest(), $type);
    }

    public function isAcceptJson(): bool
    {
        return $this->isAccept('application/json');
    }

    public function isApiCall(): bool
    {
        return $this->requestInspector->isApiCall($this->getServerRequest());
    }
}
