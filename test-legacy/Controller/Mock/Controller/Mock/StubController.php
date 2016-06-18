<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Test\Controller\Mock\Controller\Mock;

use Windwalker\Core\Controller\AbstractController;

/**
 * The MockController class.
 *
 * @since  {DEPLOY_VERSION}
 */
class StubController extends AbstractController
{
	/**
	 * doExecute
	 *
	 * @return  mixed
	 */
	protected function doExecute()
	{
		return 'mock';
	}
}
