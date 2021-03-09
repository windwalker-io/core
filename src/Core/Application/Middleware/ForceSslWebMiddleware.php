<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Middleware\MiddlewareInterface;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The ForceSslWebMiddleware class.
 *
 * @since  3.5
 */
class ForceSslWebMiddleware extends AbstractWebMiddleware
{
    use OptionAccessTrait;

    /**
     * ForceSslWebMiddleware constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Middleware logic to be invoked.
     *
     * @param   Request                      $request  The request.
     * @param   Response                     $response The response.
     * @param   callable|MiddlewareInterface $next     The next middleware.
     *
     * @return  Response
     */
    public function __invoke(Request $request, Response $response, $next = null)
    {
        if ($this->getOption('enabled')) {
            $uri = $request->getUri();

            if ($uri->getScheme() === 'http') {
                $uri = $uri->withScheme('https');

                $res = new RedirectResponse(
                    $uri,
                    $this->normalizeCode($this->getOption('redirect_code', 303))
                );

                $hstsOptions = (array) $this->getOption('hsts');

                if ($hstsOptions['enabled'] ?? false) {
                    $header = 'max-age=' . ($hstsOptions['max_age'] ?? 31536000);

                    if ($hstsOptions['include_subdomain'] ?? false) {
                        $header .= '; includeSubDomains';
                    }

                    if ($hstsOptions['preload'] ?? false) {
                        $header .= '; preload';
                    }

                    $res = $res->withHeader('Strict-Transport-Security', $header);
                }

                return $res;
            }
        }

        return $next($request, $response);
    }

    /**
     * normalizeCode
     *
     * @param int $code
     *
     * @return  int
     *
     * @since  3.5.23.2
     */
    protected function normalizeCode(int $code): int
    {
        if ($code < 300 && $code >= 400) {
            $code = 303;
        }

        return $code;
    }
}
