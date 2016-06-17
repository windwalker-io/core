<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Windwalker\Core\Model\Exception\ValidateFailException;
use Windwalker\Data\Data;

/**
 * The ErrorHandlingMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
class ErrorHandlingMiddleware extends AbstractControllerMiddleware
{
	/**
	 * Call next middleware.
	 *
	 * @param   ControllerData $data
	 *
	 * @return mixed
	 * @throws \Exception
	 * @throws \Throwable
	 */
	public function execute($data = null)
	{
		try
		{
			$result = $this->next->execute($data);

			return $this->controller->processSuccess($result);
		}
		catch (ValidateFailException $e)
		{
			$message = $e->getMessage();

			return $this->controller->processFailure(null, $message);
		}
		catch (\Exception $e)
		{
			if ($this->controller->app->get('system.debug'))
			{
				//throw $e;
			}

			$message = $e->getMessage();

			return $this->controller->processFailure(null, $message);
		}
		catch (\Throwable $e)
		{
			if ($this->controller->app->get('system.debug'))
			{
				throw $e;
			}

			$message = $e->getMessage();

			return $this->controller->processFailure(null, $message);
		}
	}
}
