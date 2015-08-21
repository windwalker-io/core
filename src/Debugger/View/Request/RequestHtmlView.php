<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger\View\Request;

use Windwalker\Debugger\View\AbstractDebuggerHtmlView;
use Windwalker\Html\Grid\Grid;
use Windwalker\Registry\Registry;

/**
 * The SystemHtmlView class.
 * 
 * @since  2.1.1
 */
class RequestHtmlView extends AbstractDebuggerHtmlView
{
	/**
	 * prepareData
	 *
	 * @param \Windwalker\Data\Data $data
	 *
	 * @return  void
	 */
	protected function prepareData($data)
	{
		$data->collector = $data->item['collector'];
	}
}
