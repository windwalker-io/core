<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\Integrate\View\Stub;

use Windwalker\Core\View\HtmlView;

/**
 * The StubHtmlView class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class StubHtmlView extends HtmlView
{
	/**
	 * getRegisteredPaths
	 *
	 * @return  \SplPriorityQueue
	 */
	public function getRegisteredPaths()
	{
		$this->registerPaths();

		return clone $this->renderer->getPaths();
	}
}
