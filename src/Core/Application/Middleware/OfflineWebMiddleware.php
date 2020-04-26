<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\Core\Application\OfflineService;
use Windwalker\Core\Widget\WidgetHelper;
use Windwalker\DI\Annotation\Inject;
use Windwalker\Middleware\MiddlewareInterface;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The OfflineWebMiddleware class.
 *
 * @since  __DEPLOY_VERSION__
 */
class OfflineWebMiddleware extends AbstractWebMiddleware
{
    use OptionAccessTrait;

    /**
     * @Inject()
     *
     * @var OfflineService
     */
    protected $offlineService;

    /**
     * SystemListener constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(Request $request, Response $response, $next = null)
    {
        $this->offlineService->offlineIfEnabled($this->options);

        return $next($request, $response);
    }
}
