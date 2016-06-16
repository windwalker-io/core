<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Package\Middleware;

use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Middleware\AbstractMiddleware;
use Windwalker\Middleware\Psr7MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * The AbstractApplicationMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
abstract class AbstractPackageMiddleware extends AbstractMiddleware implements Psr7MiddlewareInterface
{
	/**
	 * Property app.
	 *
	 * @var  AbstractPackage
	 */
	protected $package;

	/**
	 * AbstractApplicationMiddleware constructor.
	 *
	 * @param AbstractPackage $package
	 */
	public function __construct(AbstractPackage $package)
	{
		$this->package = $package;
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
