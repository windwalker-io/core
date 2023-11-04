<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The AjaxInspector class.
 */
class AjaxInspector
{
    protected ?\Closure $detector = null;

    public function isAjax(ServerRequestInterface $request): bool
    {
        return (bool) $this->getDetector()($request);
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

    public function getDetector(): ?\Closure
    {
        return $this->detector ??= $this->getDefaultDetector();
    }

    /**
     * @param  \Closure|null  $detector
     *
     * @return  static  Return self to support chaining.
     */
    public function setDetector(?\Closure $detector): static
    {
        $this->detector = $detector;

        return $this;
    }
}
