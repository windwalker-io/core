<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Windwalker\Http\Response\AbstractContentTypeResponse;
use Windwalker\Http\Response\EmptyResponse;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Http\Response\JsonResponse;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Http\Response\Response;
use Windwalker\Http\Response\TextResponse;
use Windwalker\Http\Response\XmlResponse;

/**
 * The ResponseFactory class.
 */
class ResponseFactory
{
    /**
     * ResponseFactory constructor.
     */
    public function __construct(
        protected ?string $body = null,
        protected int $status = 200,
        protected array $headers = [],
    ) {
        //
    }

    protected function prepare(mixed $body, ?int $status, array $headers): array
    {
        return [
            $body ?? $this->body,
            $status ?? $this->status,
            array_merge($this->headers, $headers)
        ];
    }

    /**
     * Create a new response.
     *
     * @param  mixed     $body
     * @param  int|null  $status  HTTP status code; defaults to 200
     * @param  array     $headers
     *
     * @return Response
     */
    public function response(mixed $body = '', ?int $status = null, array $headers = []): Response
    {
        [$body, $status, $headers] = $this->prepare($body, $status, $headers);

        return Response::fromString((string) $body, $status, $headers);
    }

    public function json(mixed $body, ?int $status = null, array $headers = [], int $options = 0): JsonResponse
    {
        [$body, $status, $headers] = $this->prepare($body, $status, $headers);

        return new JsonResponse($body, $status, $headers, $options);
    }

    public function xml(mixed $body, ?int $status = null, array $headers = []): XmlResponse
    {
        [$body, $status, $headers] = $this->prepare($body, $status, $headers);

        return new XmlResponse($body, $status, $headers);
    }

    public function empty(int $status = 204, array $headers = []): EmptyResponse
    {
        [, $status, $headers] = $this->prepare('', $status, $headers);

        return new EmptyResponse($status, $headers);
    }

    public function html(mixed $body, ?int $status = null, array $headers = []): HtmlResponse
    {
        [$body, $status, $headers] = $this->prepare($body, $status, $headers);

        return new HtmlResponse($body, $status, $headers);
    }

    public function text(mixed $body, ?int $status = null, array $headers = []): TextResponse
    {
        [$body, $status, $headers] = $this->prepare($body, $status, $headers);

        return new TextResponse($body, $status, $headers);
    }

    public function redirect(string|\Stringable $body, int $status = 303, array $headers = []): RedirectResponse
    {
        [$body, $status, $headers] = $this->prepare($body, $status, $headers);

        return new RedirectResponse($body, $status, $headers);
    }

    public function attachment(mixed $body, ?int $status = null, array $headers = []): RedirectResponse
    {
        [$body, $status, $headers] = $this->prepare($body, $status, $headers);

        return new RedirectResponse($body, $status, $headers);
    }

    public function view(array $data, ?int $status = null, array $headers = []): ViewResponse
    {
        [, $status, $headers] = $this->prepare(null, $status, $headers);

        return new ViewResponse($data, $status, $headers);
    }
}
