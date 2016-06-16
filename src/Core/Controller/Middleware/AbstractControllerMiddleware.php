<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Windwalker\Core\Controller\AbstractController;
use Windwalker\Data\Data;
use Windwalker\Middleware\AbstractMiddleware;

/**
 * The AbstractControllerMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
abstract class AbstractControllerMiddleware extends AbstractMiddleware
{
	/**
	 * Property controller.
	 *
	 * @var  AbstractController
	 */
	protected $controller;

	/**
	 * AbstractControllerMiddleware constructor.
	 *
	 * @param AbstractController $controller
	 */
	public function __construct(AbstractController $controller)
	{
		$this->controller = $controller;
	}

	/**
	 * Call next middleware.
	 *
	 * @param   Data $data
	 *
	 * @return  string
	 */
	abstract public function execute($data = null);
}
