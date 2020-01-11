<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\DI\Annotation\Inject;
use Windwalker\Environment\Browser\Browser;
use Windwalker\Middleware\MiddlewareInterface;
use Windwalker\Session\Session;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The SessionRaiseMiddleware class.
 *
 * @since  3.0
 */
class SessionRaiseMiddleware extends AbstractWebMiddleware
{
    use OptionAccessTrait;

    /**
     * @Inject()
     *
     * @var Browser
     */
    protected $browser;

    /**
     * SessionRaiseMiddleware constructor.
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
        if ($this->getOption('ignore_robots', true) && $this->browser->isRobot()) {
            return $next($request, $response);
        }

        if (!$this->getOption('enabled', true)) {
            return $next($request, $response);
        }

        $session = $this->app->container->get(Session::class);

        $session->start();

        return $next($request, $response);
    }
}
