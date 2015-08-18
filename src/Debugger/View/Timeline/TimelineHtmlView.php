<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger\View\Timeline;

use Windwalker\Debugger\Helper\TimelineHelper;
use Windwalker\Debugger\View\AbstractDebuggerHtmlView;

/**
 * The SystemHtmlView class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class TimelineHtmlView extends AbstractDebuggerHtmlView
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
		$profiler = $data->item['profiler'];

		// Find system process points
		$points = (array) $profiler->getPoints();

		$data->systemProcess = TimelineHelper::prepareTimeline($points, 'system.process');
		$data->allProcess = TimelineHelper::prepareTimeline($points);
	}
}
