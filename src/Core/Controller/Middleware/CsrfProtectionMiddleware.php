<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Controller\Middleware;

use Windwalker\Core\Security\CsrfProtection;

/**
 * The CsrfTokenMiddleware class.
 *
 * @since  {DEPLOY_VERSION}
 */
class CsrfProtectionMiddleware extends AbstractControllerMiddleware
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
		CsrfProtection::validate();

		return $this->next->execute($data);
	}
}
