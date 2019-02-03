<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\Middleware\MiddlewareInterface;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The ForceSslWebMiddleware class.
 *
 * @since  __DEPLOY_VERSION__
 */
class ForceSslWebMiddleware extends AbstractWebMiddleware
{
    use OptionAccessTrait;

    public const TYPE_REDIRECT = 1;
    public const TYPE_HSTS = 2;

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
            }

            if ($this->getOption('redirect_type') === static::TYPE_HSTS) {
                header('Strict-Transport-Security: max-age=' . $this->getOption('max_age', 31536000));
            } else {
                $this->app->redirect($uri, $this->getOption('redirect_code', 303));
            }

            die;
        }

        return $next($request, $response);
    }
}
