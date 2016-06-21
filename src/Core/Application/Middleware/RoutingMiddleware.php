<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Application\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Windwalker\Core\Package\PackageResolver;
use Windwalker\Core\Router\CoreRouter;
use Windwalker\Middleware\MiddlewareInterface;
use Windwalker\Router\Exception\RouteNotFoundException;
use Windwalker\Router\Route;
use Windwalker\String\StringHelper;
use Windwalker\String\StringNormalise;
use Windwalker\Uri\UriHelper;
use Windwalker\Utilities\ArrayHelper;

/**
 * The RoutingMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
class RoutingMiddleware extends AbstractWebMiddleware
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
		$router = $this->getRouter();

		$routes = $router::loadRoutingFromFiles((array) $this->app->get('routing.files'));

		$router->registerRawRouting($routes, $this->getPackageResolver());

		$route = $this->match($router);

		$request = $this->handleMatched($route, $request);

		return $next($request, $response);
	}

	/**
	 * match
	 *
	 * @param CoreRouter $router
	 * @param string     $route
	 *
	 * @return  Route
	 */
	public function match(CoreRouter $router, $route = null)
	{
		$route = $route ? : $this->app->server->uri->route;
		$route = $route ? : '/';

		$input = $this->app->input;
		$request = $this->app->request;

		if ($request->hasHeader('X-HTTP-Method-Override'))
		{
			$method = $request->getHeaderLine('X-HTTP-Method-Override');
		}
		else
		{
			$method = $input->get('_method') ? : $input->getMethod();
		}

		// Pass variables to custom method
		if ($input->$method)
		{
			$httpMethod = $input->getMethod();

			$input->$method->setData($input->$httpMethod->toArray());
		}

		// Prepare option data
		$uri = $request->getUri();

		$options = array(
			'scheme' => $uri->getScheme(),
			'host'   => $uri->getHost(),
			'port'   => $uri->getPort()
		);

		try
		{
			return $router->match($route, $method, $options);
		}
		catch (RouteNotFoundException $e)
		{
			$route = explode('/', $route);
			$controller = array_pop($route);
			$class = StringNormalise::toClassNamespace(
				sprintf(
					'%s\Controller\%s\%s',
					implode($route, '\\'),
					ucfirst($controller),
					$router->fetchControllerSuffix($method)
				)
			);
			
			if (!class_exists($class))
			{
				throw new RouteNotFoundException($e->getMessage(), $e->getCode(), $e);
			}
			
			$matched = new Route(implode($route, '.') . '@' . $controller, implode($route, '/'));
			
			$matched->setExtra(array(
				'controller' => $class
			));
			
			return $matched;
		}
	}

	/**
	 * getRouter
	 *
	 * @return  CoreRouter
	 */
	protected function getRouter()
	{
		return $this->app->container->get('router');
	}

	/**
	 * getPackageResolver
	 *
	 * @return  PackageResolver
	 */
	protected function getPackageResolver()
	{
		return $this->app->container->get('package.resolver');
	}

	/**
	 * handleMatched
	 *
	 * @param Route   $route
	 * @param Request $request
	 *
	 * @return Request
	 */
	protected function handleMatched(Route $route, Request $request)
	{
		$name = $route->getName();

		list($packageName, $routeName) = StringHelper::explode('@', $name, 2, 'array_unshift');

		$variables = $route->getVariables();
		$extra     = $route->getExtra();
		$input     = $this->app->input;

		// Save to input & ServerRequest
		foreach ($variables as $name => $value)
		{
			$input->def($name, UriHelper::decode($value));
			// Don't forget to do an explicit set on the GET superglobal.
			$input->get->def($name, UriHelper::decode($value));

			$request = $request->withAttribute($name, UriHelper::decode($value));
		}

		$this->app->server->setRequest($request);

		// Store to config
		$this->app->set('route', [
			'name'       => $route,
			'package'    => $packageName,
			'short_name' => $routeName,
			'extra'      => $extra
		]);

		// Package
		$package = $this->getPackageResolver()->resolvePackage($packageName);

		$this->app->container->share('current.package', $package);
		$this->app->container->share('current.route', $route);

		return $request->withAttribute('_controller', ArrayHelper::getValue($extra, 'controller'));
	}
}
