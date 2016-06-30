<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Application\Middleware;

use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Middleware\Psr7InvokableInterface;

/**
 * The AbstractApplicationMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
abstract class AbstractWebMiddleware implements Psr7InvokableInterface
{
	/**
	 * Property app.
	 *
	 * @var  WebApplication
	 */
	protected $app;

	/**
	 * Property package.
	 *
	 * @var  AbstractPackage
	 */
	protected $package;

	/**
	 * AbstractApplicationMiddleware constructor.
	 *
	 * @param WebApplication  $app
	 * @param AbstractPackage $package
	 */
	public function __construct(WebApplication $app, AbstractPackage $package = null)
	{
		$this->app = $app;
		$this->package = $package;
	}
}
