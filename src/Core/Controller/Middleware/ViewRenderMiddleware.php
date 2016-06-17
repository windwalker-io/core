<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Windwalker\Core\Response\HtmlViewResponse;
use Windwalker\Data\Data;
use Windwalker\Http\Response\HtmlResponse;

/**
 * The ViewRenderMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
class ViewRenderMiddleware extends AbstractControllerMiddleware
{
	/**
	 * Call next middleware.
	 *
	 * @param   Data $data
	 *
	 * @return  string
	 */
	public function execute($data = null)
	{
		$view = $this->next->execute($data);

		$this->controller->setResponse(new HtmlResponse);

		return $view;
	}
}
