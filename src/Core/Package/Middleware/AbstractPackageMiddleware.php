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
use Windwalker\Middleware\Psr7InvokableInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * The AbstractApplicationMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
abstract class AbstractPackageMiddleware implements Psr7InvokableInterface
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
}
