<?php

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The RequestInspector class.
 */
class RequestInspector
{
    protected ?\Closure $apiCallDetector = null;

    public function isApiCall(ServerRequestInterface $request): bool
    {
        return (bool) $this->getApiCallDetector()($request);
    }

    protected function getDefaultDetector(): \Closure
    {
        return static function (ServerRequestInterface $request) {
            $requestWith = $request->getServerParams()['HTTP_X_REQUESTED_WITH'] ?? '';

            if (strtolower($requestWith) === 'xmlhttprequest') {
                return true;
            }

            $contentType = $request->getHeaderLine('content-type');

            if (str_contains($contentType, 'application/json')) {
                return true;
            }

            if (str_contains($contentType, 'application/xml')) {
                return true;
            }

            if (str_contains($contentType, 'text/xml')) {
                return true;
            }

            return false;
        };
    }

    public function getApiCallDetector(): ?\Closure
    {
        return $this->apiCallDetector ??= $this->getDefaultDetector();
    }

    /**
     * @param  \Closure|null  $apiCallDetector
     *
     * @return  static  Return self to support chaining.
     */
    public function setApiCallDetector(?\Closure $apiCallDetector): static
    {
        $this->apiCallDetector = $apiCallDetector;

        return $this;
    }

    public function isAccept(ServerRequestInterface $request, string $type): bool
    {
        return str_contains(
            $request->getHeaderLine('accept'),
            $type
        );
    }
}
