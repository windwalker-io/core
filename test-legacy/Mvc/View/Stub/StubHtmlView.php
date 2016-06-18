<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Test\Mvc\View\Stub;

use Windwalker\Core\View\PhpHtmlView;

/**
 * The StubHtmlView class.
 * 
 * @since  2.1.1
 */
class StubHtmlView extends PhpHtmlView
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
