<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Test\Mvc\View\Stub;

use Windwalker\Core\Model\Model;
use Windwalker\Core\View\HtmlView;
use Windwalker\Core\View\ViewModel;

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
