<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger\Controller\Exception;

use Windwalker\Core\Controller\Controller;

/**
 * The GetController class.
 * 
 * @since  2.1.1
 */
class GetController extends Controller
{
	/**
	 * doExecute
	 *
	 * @return  mixed
	 */
	protected function doExecute()
	{
		$view = $this->getView();
		$model = $this->getModel('Profiler');

		$view['item'] = $model->getItem($this->input->get('id'));

		return $view->render();
	}
}
