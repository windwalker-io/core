<?php

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Jenssegers\Agent\Agent;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The Browser class.
 */
class Browser extends Agent
{
    public static function fromRequest(ServerRequestInterface $request): static
    {
        $headers = $request->getHeaders();

        foreach ($headers as $header => $values) {
            $headers[$header] = implode(', ', (array) $values);
        }

        return new Browser($headers, $request->getHeaderLine('user-agent'));
    }
}
