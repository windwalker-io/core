<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

/**
 * The TestMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
class TestMiddleware extends AbstractControllerMiddleware
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
		$result = $this->next->execute($data);

		$result .= 'test';

		return $result;
	}
}
