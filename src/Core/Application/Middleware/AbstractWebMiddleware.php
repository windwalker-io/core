<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Application\Middleware;

use Windwalker\Core\Application\WebApplication;
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
	 * AbstractApplicationMiddleware constructor.
	 *
	 * @param WebApplication $app
	 */
	public function __construct(WebApplication $app)
	{
		$this->app = $app;
	}
}
