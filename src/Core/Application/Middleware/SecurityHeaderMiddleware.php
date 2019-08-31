<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\Middleware\MiddlewareInterface;
use Windwalker\Utilities\Classes\OptionAccessTrait;
use function Windwalker\tap;

/**
 * The SecurityHeaderMiddleware class.
 *
 * @since  __DEPLOY_VERSION__
 */
class SecurityHeaderMiddleware extends AbstractWebMiddleware
{
    use OptionAccessTrait;

    public const X_FRAME_DENY = 'DENY';
    public const X_FRAME_SAMEORIGIN = 'SAMEORIGIN';
    public const X_FRAME_ALLOW_FROM = 'ALLOW_FROM';

    /**
     * SecurityHeaderMiddleware constructor.
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
     * @param Request                      $request  The request.
     * @param Response                     $response The response.
     * @param callable|MiddlewareInterface $next     The next middleware.
     *
     * @return  Response
     */
    public function __invoke(Request $request, Response $response, $next = null)
    {
        /** @var Response $response */
        $response = $next($request, $response);

        // X-Frame-Options
        if ($xFrame = $this->getOption('x-frame-options', static::X_FRAME_SAMEORIGIN)) {
            if ($xFrame === static::X_FRAME_ALLOW_FROM) {
                $xFrame .= ' ' . $this->getOption('allow_from');
            }

            $response = $response->withHeader('X-Frame-Options', $xFrame);
        }

        if ($xContentType = $this->getOption('x-content-type-options', 'nosniff')) {
            $response = $response->withHeader('X-Content-Type-Options', $xContentType);
        }

        if ($xss = $this->getOption('x-xss-protection', '1; mode=block')) {
            $response = $response->withHeader('X-XSS-Protection', $xss);
        }

        return $response;
    }
}
