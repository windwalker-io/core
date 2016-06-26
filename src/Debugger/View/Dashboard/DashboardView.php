<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger\View\Dashboard;

use Windwalker\Data\Data;
use Windwalker\Debugger\View\AbstractDebuggerHtmlView;

/**
 * The DashboardHtmlView class.
 * 
 * @since  2.1.1
 */
class DashboardView extends AbstractDebuggerHtmlView
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
		$route = $this->getPackage()->route;

		$data->items = $this->model->getItems();

		foreach ($data->items as $k => $item)
		{
			$item = new Data($item);
			$collector = $item['collector'];

			$item->url  = $collector['system.uri.full'];
			$item->link = $route->encode('system', array('id' => $item->id));
			$item->method = $collector['system.method.custom'] ? : $collector['system.method.http'];
			$item->ip   = $collector['system.ip'];
			$item->time = $collector['system.time'];

			$item->status = $collector['system.http.status'];

			if ($item->status == 200)
			{
				$item->status_style = 'label label-success';
			}
			elseif (in_array($item->status, array(301, 302, 303)))
			{
				$item->status_style = 'label label-warning';
			}
			else
			{
				$item->status_style = 'label label-danger';
			}

			$item->exception = new Data($collector['exception']);

			$data->items[$k] = $item;
		}
	}
}
