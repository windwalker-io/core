<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Phoenix\Controller\AbstractPostController;
use Windwalker\Core\Response\Buffer\JsonBuffer;
use Windwalker\Core\Utilities\Debug\BacktraceHelper;
use Windwalker\Core\View\AbstractView;
use Windwalker\Data\Data;
use Windwalker\Debugger\Helper\DebuggerHelper;
use Windwalker\Http\Helper\ResponseHelper;
use Windwalker\Utilities\ArrayHelper;

/**
 * The RenderViewMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
class JsonApiMiddleware extends AbstractControllerMiddleware
{
	/**
	 * Call next middleware.
	 *
	 * @param   ControllerData $data
	 *
	 * @return  string
	 */
	public function execute($data = null)
	{
		if (class_exists(DebuggerHelper::class))
		{
			DebuggerHelper::disableConsole();
		}

		try
		{
			$result = $this->next->execute($data);

			if ($result instanceof AbstractView)
			{
				$result = $result->getHandledData();
			}

			$message = $this->getMessage();

			return new JsonBuffer($message, $result);
		}
		catch (\Exception $e)
		{
			return $this->handleException($e);
		}
		catch (\Throwable $e)
		{
			return $this->handleException(new \ErrorException($e->getMessage(), $e->getCode(), $e));
		}
	}

	/**
	 * handleException
	 *
	 * @param \Exception $e
	 *
	 * @return  JsonBuffer
	 */
	protected function handleException(\Exception $e)
	{
		$data = [];

		if ($this->controller->app->get('system.debug'))
		{
			$data['backtrace'] = BacktraceHelper::normalizeBacktraces($e->getTrace());
		}

		$code = $e->getCode();

		if (!ResponseHelper::validateStatus($code))
		{
			$code = 500;
		}

		$this->controller->setResponse(
			$this->controller->getResponse()->withStatus($code)
		);

		return new JsonBuffer($e->getMessage(), $data, false, $e->getCode());
	}

	/**
	 * getMessage
	 *
	 * @return  string
	 */
	protected function getMessage()
	{
		list($url, $msg, $type) = $this->controller->getRedirect(true);

		if (!$msg)
		{
			$msg = $this->controller->app->session->getFlashBag()->takeAll();

			$msg = implode("\n", ArrayHelper::flatten($msg));
		}

		return $msg;
	}
}
