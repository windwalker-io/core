<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Windwalker\Debugger\Helper\DebuggerHelper;
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
				$message = $exception->getMessage();

				if (Mbstring::isUtf8($message))
				{
					$message = str_replace('%20', ' ', rawurlencode($message));
				}

				$data = ['error' => $exception->getMessage()];
				$response = (new JsonResponse($data))->withStatus($exception->getCode(), $message);

				$this->controller
					->app
					->getServer()
					->getOutput()
					->respond($response);

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
