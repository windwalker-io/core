<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Windwalker\Core\Response\Buffer\JsonBuffer;
use Windwalker\Data\Data;

/**
 * The RenderViewMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
class JsonFormatMiddleware extends AbstractControllerMiddleware
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
		try
		{
			$result = $this->next->execute($data);

			return new JsonBuffer(null, $result);
		}
		catch (\Exception $e)
		{
			$data = [];

			if (WINDWALKER_DEBUG)
			{
				$traces = array();

				foreach ((array) $e->getTrace() as $trace)
				{
					$trace = new Data($trace);

					$traces[] = array(
						'file' => $trace['file'] ? $trace['file'] . ' (' . $trace['line'] . ')' : null,
						'function' => ($trace['class'] ? $trace['class'] . '::' : null) . $trace['function'] . '()'
					);
				}

				$data['backtrace'] = $traces;
			}

			$this->controller->setResponse(
				$this->controller->getResponse()->withStatus($e->getCode())
			);

			return new JsonBuffer($e->getMessage(), $data, false, $e->getCode());
		}
	}
}
