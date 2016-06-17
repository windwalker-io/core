<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Windwalker\Data\Data;
use Windwalker\Http\Response\HtmlResponse;

/**
 * The HtmlResponseMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
class HtmlResponseMiddleware extends AbstractControllerMiddleware
{
	/**
	 * Call next middleware.
	 *
	 * @param   ControllerData $data
	 *
	 * @return  mixed
	 */
	public function execute($data = null)
	{
		$this->controller->setResponse(new HtmlResponse('', $data->response->getStatusCode(), $data->response->getHeaders()));

		$result = $this->next->execute($data);

		$this->controller->setRedirect(null);

		return $result;
	}
}
