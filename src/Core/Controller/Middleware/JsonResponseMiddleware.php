<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Windwalker\Core\Error\ErrorManager;
use Windwalker\Debugger\Helper\DebuggerHelper;
use Windwalker\Http\Helper\ResponseHelper;
use Windwalker\Http\Response\JsonResponse;
use Windwalker\String\Mbstring;

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
	 * @throws \InvalidArgumentException
	 * @throws \UnexpectedValueException
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
				/** @var $exception \Exception|\Throwable */
				$data = [
					'error' => !WINDWALKER_DEBUG ? $exception->getMessage() : sprintf(
						'#%d %s - File: %s (%d)',
						$exception->getCode(),
						$exception->getMessage(),
						$exception->getFile(),
						$exception->getLine()
					)
				];

				$response = (new JsonResponse($data))->withStatus(
					ErrorManager::normalizeCode($exception->getCode()),
					ErrorManager::normalizeMessage($exception->getMessage())
				);

				$this->controller
					->app
					->getServer()
					->getOutput()
					->respond($response);
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
