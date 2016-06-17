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
use Windwalker\Http\Response\JsonResponse;

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
	 * @param   Data $data
	 *
	 * @return  string
	 */
	public function execute($data = null)
	{
		$this->controller->setResponse(new JsonResponse);

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

			return new JsonBuffer($e->getMessage(), $data, false, $e->getCode());
		}
	}
}
