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
use function Windwalker\arr;
use Windwalker\Debugger\Helper\DebuggerHelper;
use Windwalker\Middleware\MiddlewareInterface;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The IpAllowWebMiddleware class.
 *
 * @since  1.8.13
 */
class IpAllowWebMiddleware extends AbstractWebMiddleware
{
    use OptionAccessTrait;

    /**
     * Property ips.
     *
     * @var  array|string
     */
    protected $ips;

    /**
     * Property enabled.
     *
     * @var  bool
     */
    protected $enabled;

    /**
     * IPBlockMiddleware constructor.
     *
     * @param string|array $ips
     * @param array        $options
     */
    public function __construct($ips = [], array $options = [])
    {
        $this->ips     = is_string($ips) ? explode(',', $ips) : $ips;
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
        if ($this->getOption('enabled', true)) {
            $ip = DebuggerHelper::getCurrentIp();

            $ips = arr($this->ips)
                ->map('trim')
                ->filter('strlen');

            if (!$ips->contains('all') && !$ips->contains($ip)) {
                if (!$this->getOption('debug')) {
                    DebuggerHelper::disableConsole();
                }

                return $response->withStatus((int) $this->getOption('status_code', 404));
            }
        }

        return $next($request, $response);
    }
}
