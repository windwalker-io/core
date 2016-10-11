<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Windwalker\Core\Controller\AbstractController;
use Windwalker\Middleware\AbstractMiddleware;

/**
 * The AbstractControllerMiddleware class.
 *
 * @since  3.0
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
	 * @param   ControllerData $data
	 *
	 * @return  mixed
	 */
	abstract public function execute($data = null);
}
