<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Application\Middleware;

use Windwalker\Core\Application\WebApplication;
use Windwalker\Middleware\AbstractMiddleware;
use Windwalker\Middleware\Psr7MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * The AbstractApplicationMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
abstract class AbstractWebMiddleware extends AbstractMiddleware implements Psr7MiddlewareInterface
{
	/**
	 * Property app.
	 *
	 * @var  WebApplication
	 */
	protected $app;

	/**
	 * AbstractApplicationMiddleware constructor.
	 *
	 * @param WebApplication $app
	 */
	public function __construct(WebApplication $app)
	{
		$this->app = $app;
	}

	/**
	 * Call next middleware.
	 *
	 * @param Request  $request
	 * @param Response $response
	 *
	 * @return Response
	 */
	public function execute($request = null, $response = null)
	{
		return call_user_func($this, $request, $response, $this->next);
	}
}
