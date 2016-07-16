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
use Windwalker\Core\Router\MainRouter;
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
		$router = $this->app->getRouter();

		$this->app->triggerEvent('onBeforeRouting', [
			'app'      => $this->app,
			'router'   => $router,
			'request'  => $request,
			'response' => $response
		]);

		$route = $this->match($router);

		$this->app->triggerEvent('onAfterRouteMatched', [
			'app'     => $this->app,
			'router'  => $router,
			'matched' => $route,
			'request' => $request,
			'response' => $response
		]);

		$request = $this->handleMatched($route, $request);

		$this->app->triggerEvent('onAfterRouting', [
			'app'     => $this->app,
			'router'  => $router,
			'matched' => $route,
			'request' => $request,
			'response' => $response
		]);

		return $next($request, $response);
	}

	/**
	 * match
	 *
	 * @param MainRouter $router
	 * @param string     $route
	 *
	 * @return  Route
	 */
	public function match(MainRouter $router, $route = null)
	{
		$route = $route ? : $this->app->uri->route;
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
			if (!$this->app->get('routing.simple_route', false))
			{
				throw $e;
			}

			// Simple routing
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
			
			// Find package
			$ns = implode('\\', array_map('ucfirst', $route)) . '\\' . ucfirst(end($route)) . 'Package';
			
			$resolver = $this->getPackageResolver();
			$package = $resolver->resolvePackage($resolver->getAlias($ns));
			
			$packageName = $package ? $package->getName() : implode('.', $route);

			if (!class_exists($class))
			{
				throw new RouteNotFoundException($e->getMessage(), $e->getCode(), $e);
			}

			$matched = new Route($packageName . '@' . $controller, implode($route, '/'));

			$matched->setExtraValues(array(
				'controller' => $class
			));
			
			return $matched;
		}
	}

	/**
	 * getRouter
	 *
	 * @return  MainRouter
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
		$extra     = $route->getExtraValues();
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
			'matched'    => $route->getName(),
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
