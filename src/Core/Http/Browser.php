<?php

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Asika\Agent\Agent;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The Browser class.
 *
 * @deprecated Use {@see BrowserNext} directly.
 */
class Browser extends Agent
{
    public static function fromRequest(ServerRequestInterface $request): static
    {
        $headers = $request->getHeaders();

        foreach ($headers as $header => $values) {
            $headers[$header] = implode(', ', (array) $values);
        }

        return new static($headers, $request->getHeaderLine('user-agent'));
    }
}
