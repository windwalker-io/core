<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\Core\Error\ErrorHandler;
use Windwalker\Core\Renderer\RendererHelper;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Middleware\MiddlewareInterface;

/**
 * The ErrorHandlingMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
class ErrorHandlingMiddleware extends AbstractWebMiddleware
{
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
		try
		{
			return $next($request, $response);
		}
		catch (\Exception $e)
		{
			$this->exception($e, $request, $response);
		}
		catch (\Throwable $e)
		{
			$this->exception($e, $request, $response);
		}

		return $response;
	}

	/**
	 * exception
	 *
	 * @param \Exception|\Throwable  $e
	 * @param Request                $request
	 * @param Response               $response
	 *
	 * @return  void
	 */
	public function exception($e, Request $request, Response $response)
	{
		$renderer = RendererHelper::getPhpRenderer();

		$body = $renderer->render($this->app->get('error.template', 'windwalker.error.default'), array('exception' => $e));

		$this->app->server->getOutput()->respond(new HtmlResponse($body, $e->getCode() ? : 500));

		exit();
	}
}
