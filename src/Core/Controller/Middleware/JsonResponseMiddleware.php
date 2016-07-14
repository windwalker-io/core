<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Windwalker\Debugger\Helper\DebuggerHelper;
use Windwalker\Http\Response\JsonResponse;

/**
 * The RenderViewMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
class JsonResponseMiddleware extends AbstractControllerMiddleware
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
		if (class_exists(DebuggerHelper::class))
		{
			DebuggerHelper::disableConsole();
		}

		$response = $data->response;

		$this->controller->setResponse(new JsonResponse(null, $response->getStatusCode(), $response->getHeaders()));

		$result = $this->next->execute($data);

		return $result;
	}
}
