<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Windwalker\Core\Application\WebApplication;
use Windwalker\Debugger\Helper\DebuggerHelper;
use Windwalker\Http\Response\JsonResponse;

/**
 * The RenderViewMiddleware class.
 *
 * @since  3.0
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

		// Simple Json Error Handler
		$this->controller->getContainer()
			->get('error.handler')
			->addHandler(function ($exception)
			{
				$this->controller->app
					->getServer()
					->getOutput()
					->respond(
						new JsonResponse(['error' => $exception->getMessage()])
					);

				die;
			}, 'default');

		$response = $data->response;

		$this->controller->setResponse(new JsonResponse(null, $response->getStatusCode(), $response->getHeaders()));

		$result = $this->next->execute($data);

		// Check is already json string.
		if (is_array($result) || is_object($result))
		{
			return json_encode($result);
		}

		return $result;
	}
}
